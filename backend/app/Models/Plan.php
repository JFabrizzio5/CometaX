<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price_cents', 'included_hours',
        'hourly_overage_rate_cents', 'billing_cycle', 'stripe_price_id',
        'status', 'max_clients', 'is_public', 'sort_order',
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
}
