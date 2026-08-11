<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Gestión de proyectos desde el panel interno: alta, detalle, actualización
 * de estado/avance, publicación de avances e hitos.
 */
class ProjectAdminController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $clientId = (string) $request->query('client_id', '');

        $projects = Project::with('client')
            ->when(in_array($status, ['activo', 'en_revision', 'finalizado'], true), fn ($query) => $query->where('status', $status))
            ->when($clientId !== '', fn ($query) => $query->where('client_id', $clientId))
            ->latest()
            ->get();

        return view('admin.projects.index', [
            'projects' => $projects,
            'clients' => Client::orderBy('name')->get(),
            'status' => $status,
            'clientId' => $clientId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.projects.form', [
            'clients' => Client::orderBy('name')->get(),
            'consultants' => Consultant::orderBy('name')->get(),
            'selectedClient' => $request->query('client'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lead_consultant_id' => ['nullable', 'exists:consultants,id'],
            'status' => ['required', 'in:activo,en_revision,finalizado'],
            'start_date' => ['nullable', 'date'],
            'estimated_delivery' => ['nullable', 'date', 'after_or_equal:start_date'],
            'progress_percent' => ['required', 'integer', 'between:0,100'],
            // La columna es decimal(8,2): sin tope, MySQL en strict mode revienta.
            'hours_budgeted' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'hours_used' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $project = Project::create($data);

        return redirect()->route('admin.projects.show', $project)
            ->with('status', "Proyecto «{$project->name}» creado.");
    }

    public function show(Project $project): View
    {
        $project->load([
            'client', 'leadConsultant', 'milestones', 'incidents', 'activities.actor', 'consultants', 'links',
            'timeEntries' => fn ($query) => $query->with('consultant', 'milestone')->orderByDesc('entry_date')->orderByDesc('id'),
        ]);

        return view('admin.projects.show', [
            'project' => $project,
            'consultants' => Consultant::orderBy('name')->get(),
        ]);
    }

    public function attachConsultant(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'consultant_id' => ['required', 'exists:consultants,id'],
            'role_label' => ['nullable', 'string', 'max:100'],
        ]);

        // syncWithoutDetaching respeta el unique (project_id, consultant_id).
        $project->consultants()->syncWithoutDetaching([
            $data['consultant_id'] => ['role_label' => $data['role_label'] ?? null],
        ]);

        return back()->with('status', 'Consultor asignado al proyecto.');
    }

    public function detachConsultant(Project $project, Consultant $consultant): RedirectResponse
    {
        $project->consultants()->detach($consultant->id);

        return back()->with('status', 'Consultor removido del proyecto.');
    }

    public function storeLink(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:github,repo,staging,produccion,doc,otro'],
            'label' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:1000'],
        ]);

        $project->links()->create($data);

        return back()->with('status', 'Enlace agregado.');
    }

    public function destroyLink(ProjectLink $link): RedirectResponse
    {
        $projectId = $link->project_id;
        $link->delete();

        return redirect()->route('admin.projects.show', $projectId)
            ->with('status', 'Enlace eliminado.');
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:activo,en_revision,finalizado'],
            'progress_percent' => ['required', 'integer', 'between:0,100'],
            'hours_used' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'hours_budgeted' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'estimated_delivery' => [
                'nullable', 'date',
                ...($project->start_date ? ['after_or_equal:'.$project->start_date->toDateString()] : []),
            ],
            'lead_consultant_id' => ['nullable', 'exists:consultants,id'],
        ]);

        $project->update($data);

        // Solo cambios visibles para el cliente (estado o avance) generan actividad.
        if ($project->wasChanged(['status', 'progress_percent'])) {
            $project->activities()->create([
                'actor_type' => Consultant::class,
                'actor_id' => auth('consultant')->id(),
                'action' => 'actualizacion',
                'description' => "Estado: {$project->status} · Avance: {$project->progress_percent}%",
                'occurred_at' => now(),
            ]);
        }

        return redirect()->route('admin.projects.show', $project)
            ->with('status', 'Proyecto actualizado.');
    }

    public function storeActivity(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
        ]);

        $project->activities()->create([
            'actor_type' => Consultant::class,
            'actor_id' => auth('consultant')->id(),
            'action' => 'avance',
            'description' => $data['description'],
            'occurred_at' => now(),
        ]);

        return redirect()->route('admin.projects.show', $project)
            ->with('status', 'Avance publicado.');
    }

    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $project->milestones()->create([
            'label' => $data['label'],
            'status' => 'pending',
            'sort_order' => ((int) $project->milestones()->max('sort_order')) + 1,
        ]);

        return redirect()->route('admin.projects.show', $project)
            ->with('status', 'Hito agregado.');
    }

    public function updateMilestone(Request $request, Milestone $milestone): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,done'],
        ]);

        $milestone->update($data);

        return redirect()->route('admin.projects.show', $milestone->project_id)
            ->with('status', 'Hito actualizado.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'proyecto';
        $slug = $base;
        $suffix = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
