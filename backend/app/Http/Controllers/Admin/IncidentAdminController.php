<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AttachIncidentEvidence;
use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Gestión de incidencias desde el panel interno: listado con filtros por
 * estado y prioridad, alta y edición de tickets.
 */
class IncidentAdminController extends Controller
{
    private const PRIORITIES = ['baja', 'media', 'urgente'];

    private const STATUSES = ['nuevo', 'revision', 'progreso', 'resuelto'];

    public function index(Request $request): View
    {
        $incidents = Incident::with(['project.client', 'assignee'])->latest()->get();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'byStatus' => $incidents->groupBy('status'),
            'projects' => Project::with('client')->orderBy('name')->get(),
            'stats' => [
                'nuevas' => $incidents->where('status', 'nuevo')->count(),
                'progreso' => $incidents->whereIn('status', ['revision', 'progreso'])->count(),
                'abiertas' => $incidents->where('status', '!=', 'resuelto')->count(),
                'resueltas_mes' => $incidents->where('status', 'resuelto')
                    ->filter(fn ($i) => $i->resolved_at && $i->resolved_at->isSameMonth(now()))->count(),
            ],
        ]);
    }

    /** Cambio rápido de estado (mover de columna en el tablero). */
    public function move(Request $request, Incident $incident): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(self::STATUSES)]]);

        $data['resolved_at'] = $data['status'] === 'resuelto' ? ($incident->resolved_at ?? now()) : null;
        $incident->update($data);

        return back()->with('status', "Incidencia «{$incident->ticket_code}» movida a {$data['status']}.");
    }

    public function create(Request $request): View
    {
        $incident = new Incident;
        $incident->project_id = $request->query('project');

        return view('admin.incidents.form', [
            'incident' => $incident,
            'projects' => Project::with('client')->orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // ticket_code lo genera el modelo en creating(); aquí no se asigna.
        $data = $this->validated($request);

        // Una incidencia dada de alta ya resuelta también lleva fecha de resolución.
        if ($data['status'] === 'resuelto') {
            $data['resolved_at'] = now();
        }

        $incident = Incident::create($data);

        return redirect()->route('admin.incidents.index')
            ->with('status', "Incidencia «{$incident->ticket_code}» creada.");
    }

    public function edit(Incident $incident): View
    {
        return view('admin.incidents.form', [
            'incident' => $incident->load('attachments'),
            'projects' => Project::with('client')->orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
    }

    public function storeAttachment(Request $request, Incident $incident, AttachIncidentEvidence $attach): RedirectResponse
    {
        $attach($incident, $request, 'equipo');

        return back()->with('status', 'Evidencia agregada.');
    }

    public function destroyAttachment(IncidentAttachment $attachment): RedirectResponse
    {
        if ($attachment->path) {
            Storage::disk('public')->delete($attachment->path);
        }
        $attachment->delete();

        return back()->with('status', 'Evidencia eliminada.');
    }

    public function update(Request $request, Incident $incident): RedirectResponse
    {
        $data = $this->validated($request);

        // Marca la resolución al entrar a «resuelto»; si el ticket se reabre, se limpia.
        if ($data['status'] === 'resuelto') {
            $data['resolved_at'] = $incident->resolved_at ?? now();
        } else {
            $data['resolved_at'] = null;
        }

        $incident->update($data);

        return redirect()->route('admin.incidents.index')
            ->with('status', "Incidencia «{$incident->ticket_code}» actualizada.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'status' => ['required', Rule::in(self::STATUSES)],
            'project_id' => ['required', 'exists:projects,id'],
            'assignee_consultant_id' => ['nullable', 'exists:consultants,id'],
        ]);
    }
}
