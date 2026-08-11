<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\DesgloseHoras;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Registro de horas de un proyecto.
 *
 * Dos caminos: captura renglón por renglón (`store`) y reconstrucción de un
 * trabajo ya entregado (`proponer` → revisión en pantalla → `confirmar`). Lo
 * reconstruido queda marcado con `source = reconstruido` y agrupado por
 * `batch_id` para poder deshacerlo completo.
 */
class TimeEntryAdminController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validarRenglon($request, $project);
        $data['source'] = 'medido';

        $project->timeEntries()->create($data);
        $this->recalcularHoras($project);

        return back()->with('status', 'Horas registradas.');
    }

    public function update(Request $request, TimeEntry $entry): RedirectResponse
    {
        $data = $this->validarRenglon($request, $entry->project);

        $entry->update($data);
        $this->recalcularHoras($entry->project);

        return back()->with('status', 'Renglón actualizado.');
    }

    public function destroy(TimeEntry $entry): RedirectResponse
    {
        $project = $entry->project;
        $entry->delete();
        $this->recalcularHoras($project);

        return back()->with('status', 'Renglón eliminado.');
    }

    /**
     * Arma la propuesta de desglose y la deja en sesión para revisarla. No
     * guarda nada: el admin la edita y confirma en el siguiente paso.
     */
    public function proponer(Request $request, Project $project, DesgloseHoras $desglose): RedirectResponse
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(array_keys(DesgloseHoras::TIPOS))],
            'horas' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha'],
            'milestone_id' => ['nullable', Rule::exists('milestones', 'id')->where('project_id', $project->id)],
            'participantes' => ['nullable', 'array'],
            'participantes.*' => ['integer', 'exists:consultants,id'],
            'incluir_qa_automatizado' => ['nullable', 'boolean'],
        ]);

        $project->load('consultants', 'leadConsultant');

        return back()
            ->with('desglose', $desglose->proponer($project, $data)->all())
            ->with('desgloseProyecto', $project->id);
    }

    /**
     * Guarda los renglones ya revisados. Cada uno viaja editable desde el form,
     * así que se validan de nuevo — la propuesta no es fuente de verdad.
     */
    public function confirmar(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'renglones' => ['required', 'array', 'min:1'],
            'renglones.*.entry_date' => ['required', 'date'],
            'renglones.*.activity' => ['required', 'string', 'max:255'],
            'renglones.*.category' => ['required', Rule::in(array_keys(TimeEntry::CATEGORIAS))],
            'renglones.*.hours' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'renglones.*.consultant_id' => ['nullable', 'exists:consultants,id'],
            'renglones.*.milestone_id' => ['nullable', Rule::exists('milestones', 'id')->where('project_id', $project->id)],
            'renglones.*.billable' => ['nullable', 'boolean'],
        ]);

        $lote = (string) Str::uuid();

        DB::transaction(function () use ($project, $data, $lote) {
            foreach ($data['renglones'] as $renglon) {
                $project->timeEntries()->create([
                    'entry_date' => $renglon['entry_date'],
                    'activity' => $renglon['activity'],
                    'category' => $renglon['category'],
                    'hours' => $renglon['hours'],
                    'consultant_id' => $renglon['consultant_id'] ?? null,
                    'milestone_id' => $renglon['milestone_id'] ?? null,
                    'billable' => (bool) ($renglon['billable'] ?? true),
                    'source' => 'reconstruido',
                    'batch_id' => $lote,
                ]);
            }
        });

        $this->recalcularHoras($project);

        $total = collect($data['renglones'])->sum('hours');

        return back()->with('status', count($data['renglones'])." renglones registrados ({$total} h). Puedes deshacer el lote completo desde la tabla.");
    }

    /**
     * Deshace un desglose completo. Solo toca renglones del proyecto indicado.
     */
    public function deshacerLote(Project $project, string $batch): RedirectResponse
    {
        $borrados = $project->timeEntries()->where('batch_id', $batch)->delete();
        $this->recalcularHoras($project);

        return back()->with('status', "Lote deshecho: {$borrados} renglones eliminados.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validarRenglon(Request $request, Project $project): array
    {
        return $request->validate([
            'entry_date' => ['required', 'date'],
            'activity' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(TimeEntry::CATEGORIAS))],
            'hours' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'consultant_id' => ['nullable', 'exists:consultants,id'],
            'milestone_id' => ['nullable', Rule::exists('milestones', 'id')->where('project_id', $project->id)],
            'incident_id' => ['nullable', Rule::exists('incidents', 'id')->where('project_id', $project->id)],
            'billable' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * `hours_used` deja de teclearse: es la suma de lo registrado. Se recalcula
     * en cada alta/baja para que el proyecto y el reporte nunca se contradigan.
     */
    private function recalcularHoras(Project $project): void
    {
        $project->forceFill([
            'hours_used' => (float) $project->timeEntries()->sum('hours'),
        ])->save();
    }
}
