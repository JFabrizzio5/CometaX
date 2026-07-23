<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidencia de una incidencia: imagen subida o enlace externo (Drive, video…).
 */
#[Fillable(['incident_id', 'kind', 'path', 'url', 'label', 'source'])]
class IncidentAttachment extends Model
{
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
