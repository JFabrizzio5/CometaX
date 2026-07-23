<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\Project;
use App\Models\ProjectActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal del cliente, multi-sección (resumen, proyectos, incidencias,
 * facturación, contratos, calendario, soporte). TODA consulta va scopeada al
 * cliente del usuario autenticado; los detalles verifican propiedad (403 si no).
 */
class ClientDashboardController extends Controller
{
    private function client()
    {
        return auth()->user()->client;
    }

    public function index(): View
    {
        $client = $this->client()->load([
            'plan',
            'projects' => fn ($q) => $q->latest(),
            'projects.incidents',
        ]);

        $projectIds = $client->projects->pluck('id');

        return view('client.dashboard', [
            'client' => $client,
            'stats' => [
                'proyectos_activos' => $client->projects->where('status', '!=', 'finalizado')->count(),
                'horas_consumidas' => (float) $client->projects->sum('hours_used'),
                'horas_plan' => (int) ($client->plan->included_hours ?? 0),
                'incidencias_abiertas' => $client->projects->flatMap->incidents->where('status', '!=', 'resuelto')->count(),
                'proxima_cita' => Appointment::where('client_id', $client->id)
                    ->whereDate('appointment_date', '>=', Carbon::today())->where('status', '!=', 'cancelada')
                    ->orderBy('appointment_date')->orderBy('start_time')->first(),
            ],
            'proximasCitas' => Appointment::where('client_id', $client->id)
                ->whereDate('appointment_date', '>=', Carbon::today())->where('status', '!=', 'cancelada')
                ->orderBy('appointment_date')->orderBy('start_time')->limit(5)->get(),
            'avisos' => Announcement::where(fn ($q) => $q->where('client_id', $client->id)->orWhereNull('client_id'))
                ->latest('published_at')->limit(5)->get(),
            'actividad' => ProjectActivity::whereIn('project_id', $projectIds)->with('project')
                ->latest('occurred_at')->limit(8)->get(),
        ]);
    }

    public function projects(): View
    {
        return view('client.projects', [
            'projects' => $this->client()->projects()->withCount(['incidents'])->latest()->get(),
        ]);
    }

    public function projectShow(Project $project): View
    {
        abort_unless($project->client_id === $this->client()->id, 403);

        $project->load(['milestones', 'incidents', 'links', 'activities.actor', 'leadConsultant']);

        return view('client.project-show', ['project' => $project]);
    }

    public function incidents(): View
    {
        $client = $this->client();

        return view('client.incidents', [
            'incidents' => \App\Models\Incident::whereIn('project_id', $client->projects()->pluck('id'))
                ->with('project')->latest()->get(),
            'projects' => $client->projects()->orderBy('name')->get(),
        ]);
    }

    public function reportIncident(Request $request): RedirectResponse
    {
        $client = $this->client();

        $data = $request->validate([
            'project_id' => ['required', Rule::exists('projects', 'id')->where('client_id', $client->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(['baja', 'media', 'urgente'])],
        ]);

        \App\Models\Incident::create([
            ...$data,
            'status' => 'nuevo',
            'reporter_user_id' => auth()->id(),
        ]);

        return redirect()->route('client.incidents')->with('status', 'Incidencia reportada. La revisamos pronto.');
    }

    public function invoices(): View
    {
        return view('client.invoices', [
            'invoices' => $this->client()->invoices()->latest('invoice_date')->get(),
        ]);
    }

    public function contracts(): View
    {
        $docs = $this->client()->documents()->latest()->get();

        return view('client.contracts', [
            'contracts' => $docs->where('type', 'contract')->values(),
            'documents' => $docs->where('type', '!=', 'contract')->values(),
        ]);
    }

    public function calendar(): View
    {
        $client = $this->client();

        return view('client.calendar', [
            'proximas' => Appointment::where('client_id', $client->id)
                ->whereDate('appointment_date', '>=', Carbon::today())->where('status', '!=', 'cancelada')
                ->orderBy('appointment_date')->orderBy('start_time')->get(),
            'pasadas' => Appointment::where('client_id', $client->id)
                ->whereDate('appointment_date', '<', Carbon::today())
                ->orderByDesc('appointment_date')->limit(10)->get(),
        ]);
    }

    /** El cliente solicita una reunión; queda 'solicitada' para que el equipo confirme. */
    public function requestMeeting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meeting_type' => ['required', Rule::in(['junta_mensual', 'soporte_tecnico', 'consultoria_nuevo_proyecto', 'revision_contrato'])],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->client()->appointments()->create([...$data, 'status' => 'solicitada']);

        return back()->with('status', 'Solicitud de reunión enviada. El equipo la confirmará pronto.');
    }

    public function support(): View
    {
        return view('client.support');
    }

    public function submitSupport(Request $request): RedirectResponse
    {
        $client = $this->client();
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $destino = config('mail.from.address', 'cometax@cometax.click');
        $cuerpo = "Soporte · {$client->name}\n"
            ."Usuario: ".auth()->user()->name." <".auth()->user()->email.">\n\n"
            ."Asunto: {$data['subject']}\n\n{$data['message']}\n";

        try {
            Mail::raw($cuerpo, fn ($m) => $m->to($destino)->replyTo(auth()->user()->email, auth()->user()->name)
                ->subject("Soporte · {$client->name} · {$data['subject']}"));
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar soporte', ['exception' => $e]);

            return back()->withErrors(['message' => 'No pudimos enviar tu mensaje. Intenta más tarde.'])->withInput();
        }

        return redirect()->route('client.support')->with('status', 'Mensaje enviado. Te respondemos pronto.');
    }
}
