<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'client_id', 'project_type', 'project_type_base_price_cents', 'urgency',
    'urgency_multiplier', 'description', 'contact_name', 'contact_email',
    'calculated_total_cents', 'status', 'converted_project_id',
])]
class Quote extends Model
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }

    public function scopeItems(): BelongsToMany
    {
        return $this->belongsToMany(QuoteScopeItem::class, 'quote_quote_scope_item')
            ->withPivot('price_cents_snapshot')
            ->withTimestamps();
    }
}
