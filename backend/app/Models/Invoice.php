<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id', 'plan_id', 'stripe_invoice_id', 'concept', 'amount_cents',
    'currency', 'payment_method_masked', 'status', 'paid_at', 'invoice_date',
])]
class Invoice extends Model
{
    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'invoice_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
