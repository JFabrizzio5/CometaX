<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\Incident;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $estado = (string) $request->query('estado', '');
        $prioridad = (string) $request->query('prioridad', '');

        $incidents = Incident::with(['project.client', 'assignee'])
            ->when(in_array($estado, self::STATUSES, true), fn ($query) => $query->where('status', $estado))
            ->when(in_array($prioridad, self::PRIORITIES, true), fn ($query) => $query->where('priority', $prioridad))
            ->latest()
            ->get();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'estado' => $estado,
            'prioridad' => $prioridad,
        ]);
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
            'incident' => $incident,
            'projects' => Project::with('client')->orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
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
