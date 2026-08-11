<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id', 'consultant_id', 'milestone_id', 'incident_id', 'entry_date',
    'activity', 'category', 'source', 'hours', 'billable', 'batch_id', 'notes',
])]
class TimeEntry extends Model
{
    /**
     * Tipo de trabajo. La distinción qa_manual / qa_automatizado importa: el
     * reporte no debe describir como manual algo que corrió una suite en CI.
     */
    public const CATEGORIAS = [
        'analisis' => 'Análisis y diseño',
        'backend' => 'Backend',
        'frontend' => 'Frontend',
        'qa_manual' => 'QA manual',
        'qa_automatizado' => 'QA automatizado',
        'despliegue' => 'Despliegue',
        'reunion' => 'Reunión / seguimiento',
        'soporte' => 'Soporte e incidencias',
        'otro' => 'Otro',
    ];

    /**
     * De dónde salió la hora. Es trazabilidad interna: `reconstruido` marca el
     * desglose retroactivo de trabajo real que nadie alcanzó a registrar.
     */
    public const ORIGENES = [
        'medido' => 'Registrado en el momento',
        'reconstruido' => 'Reconstruido a posteriori',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'hours' => 'decimal:2',
            'billable' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function scopeEnRango(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('entry_date', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('entry_date', '<=', $hasta));
    }

    public function categoriaLegible(): string
    {
        return self::CATEGORIAS[$this->category] ?? $this->category;
    }
}
