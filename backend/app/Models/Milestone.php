<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'project_id', 'phase', 'label', 'description', 'sort_order', 'status',
    'starts_on', 'due_on', 'hours_budgeted',
])]
class Milestone extends Model
{
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class)->latest('occurred_at');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Horas realmente registradas contra el hito (medidas + reconstruidas).
     */
    public function hoursLogged(): float
    {
        return (float) $this->timeEntries()->sum('hours');
    }
}
