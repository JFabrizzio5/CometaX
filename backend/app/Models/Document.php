<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id', 'project_id', 'type', 'title', 'filename', 'file_path',
    'version', 'status', 'signed_date', 'term_length', 'renewal_date', 'uploaded_by',
])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'signed_date' => 'date',
            'renewal_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
