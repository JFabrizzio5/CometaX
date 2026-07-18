<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id', 'lead_consultant_id', 'name', 'slug', 'description', 'status',
    'start_date', 'estimated_delivery', 'progress_percent', 'hours_budgeted', 'hours_used',
])]
class Project extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'estimated_delivery' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leadConsultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'lead_consultant_id');
    }

    public function consultants(): BelongsToMany
    {
        return $this->belongsToMany(Consultant::class, 'project_consultant')->withPivot('role_label')->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class)->latest('occurred_at');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
