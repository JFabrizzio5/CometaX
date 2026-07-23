<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueo de agenda: día completo o rango de horas marcado como ocupado.
 */
#[Fillable(['date', 'all_day', 'start_time', 'end_time', 'reason', 'consultant_id'])]
class BlockedSlot extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'all_day' => 'boolean',
        ];
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }
}
