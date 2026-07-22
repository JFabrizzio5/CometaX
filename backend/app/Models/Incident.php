<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'project_id', 'ticket_code', 'title', 'description', 'priority', 'status',
    'assignee_consultant_id', 'reporter_user_id', 'resolved_at',
])]
class Incident extends Model
{
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // El código real sale del id autoincrement DESPUÉS del insert: derivarlo
        // de max(id) antes del insert tiene race (dos altas concurrentes chocan
        // contra el unique). El placeholder solo satisface NOT NULL + unique.
        static::creating(function (Incident $incident) {
            $incident->ticket_code ??= 'tmp-'.Str::uuid();
        });

        static::created(function (Incident $incident) {
            if (str_starts_with($incident->ticket_code, 'tmp-')) {
                $incident->forceFill(['ticket_code' => 'A-'.($incident->id + 100)])->saveQuietly();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'assignee_consultant_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }
}
