<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enlace de un proyecto (repo GitHub, staging, producción, doc, etc.).
 */
#[Fillable(['project_id', 'kind', 'label', 'url'])]
class ProjectLink extends Model
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
