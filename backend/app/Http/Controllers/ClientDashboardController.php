<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\ProjectActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal del cliente: resumen real conectado a la base (proyectos, horas del
 * plan consumidas, incidencias, agenda y avisos) y solicitud de reuniones.
 */
class ClientDashboardController extends Controller
{
    public function index(): View
    {
        $client = auth()->user()->client->load([
            'plan',
            'projects' => fn ($q) => $q->latest(),
            'projects.incidents',
        ]);

        $projectIds = $client->projects->pluck('id');

        $horasConsumidas = (float) $client->projects->sum('hours_used');
        $horasPlan = (int) ($client->plan->included_hours ?? 0);

        $incidenciasAbiertas = $client->projects
            ->flatMap->incidents
            ->where('status', '!=', 'resuelto')
            ->count();

        $proximasCitas = Appointment::where('client_id', $client->id)
            ->whereDate('appointment_date', '>=', Carbon::today())
            ->where('status', '!=', 'cancelada')
            ->orderBy('appointment_date')->orderBy('start_time')
            ->limit(5)->get();

        $avisos = Announcement::where(fn ($q) => $q->where('client_id', $client->id)->orWhereNull('client_id'))
            ->latest('published_at')->limit(5)->get();

        $actividad = ProjectActivity::whereIn('project_id', $projectIds)
            ->with('project')
            ->latest('occurred_at')->limit(8)->get();

        return view('client.dashboard', [
            'client' => $client,
            'stats' => [
                'proyectos_activos' => $client->projects->where('status', '!=', 'finalizado')->count(),
                'horas_consumidas' => $horasConsumidas,
                'horas_plan' => $horasPlan,
                'incidencias_abiertas' => $incidenciasAbiertas,
                'proxima_cita' => $proximasCitas->first(),
            ],
            'proximasCitas' => $proximasCitas,
            'avisos' => $avisos,
            'actividad' => $actividad,
        ]);
    }

    /** El cliente solicita una reunión; queda 'solicitada' para que el equipo confirme. */
    public function requestMeeting(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'meeting_type' => ['required', Rule::in([
                'junta_mensual', 'soporte_tecnico', 'consultoria_nuevo_proyecto', 'revision_contrato',
            ])],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        auth()->user()->client->appointments()->create([
            ...$data,
            'status' => 'solicitada',
        ]);

        return redirect()->route('dashboard')
            ->with('status', 'Solicitud de reunión enviada. El equipo la confirmará pronto.');
    }
}
