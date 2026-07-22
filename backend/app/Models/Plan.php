<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price_cents', 'price_domiciliado_cents',
        'included_hours', 'hourly_overage_rate_cents', 'billing_cycle',
        'stripe_price_id', 'stripe_product_id', 'stripe_price_id_recurring',
        'stripe_price_id_onetime', 'status', 'max_clients', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(PlanWaitlistEntry::class);
    }

    public function activeClientsCount(): int
    {
        return $this->clients()->count();
    }

    public function hasAvailableCapacity(): bool
    {
        if ($this->max_clients === null) {
            return true;
        }

        return $this->activeClientsCount() < $this->max_clients;
    }

    /** Precio de lista (pago único) formateado, ej. "$28,000". */
    public function priceOnetimeLabel(): string
    {
        return '$'.number_format($this->price_cents / 100, 0, '.', ',');
    }

    /** Precio preferente (domiciliado) formateado; null si no aplica. */
    public function priceDomiciliadoLabel(): ?string
    {
        if ($this->price_domiciliado_cents === null) {
            return null;
        }

        return '$'.number_format($this->price_domiciliado_cents / 100, 0, '.', ',');
    }

    /** ¿El plan tiene ambos Stripe Price creados y se puede cobrar? */
    public function isBillable(): bool
    {
        return $this->stripe_price_id_recurring !== null
            && $this->stripe_price_id_onetime !== null;
    }
}
