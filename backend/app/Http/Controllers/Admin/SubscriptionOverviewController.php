<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SubscriptionOverviewController extends Controller
{
    /** Estados de Stripe que cuentan como "está pagando". */
    private const PAYING_STATUSES = ['active', 'trialing'];

    /** Estados que exigen atención del equipo. */
    private const AT_RISK_STATUSES = ['past_due', 'unpaid', 'incomplete'];

    /** @var Collection<int, Plan>|null */
    private ?Collection $plans = null;

    public function __invoke(Request $request): View
    {
        // ?estado[]=x llega como array y reventaría al castear a string.
        $filter = is_string($request->query('estado')) ? $request->query('estado') : '';

        $clients = Client::query()
            ->with(['plan', 'subscriptions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => $this->summarize($client));

        $stats = [
            'pagando' => $clients->where('is_paying', true)->count(),
            'en_riesgo' => $clients->where('at_risk', true)->count(),
            'sin_suscripcion' => $clients->whereNull('stripe_status')->count(),
            'mrr_cents' => $clients->where('is_paying', true)->sum('price_cents'),
        ];

        if ($filter === 'pagando') {
            $clients = $clients->where('is_paying', true);
        } elseif ($filter === 'riesgo') {
            $clients = $clients->where('at_risk', true);
        } elseif ($filter === 'sin-suscripcion') {
            $clients = $clients->whereNull('stripe_status');
        }

        return view('admin.subscriptions', [
            'clients' => $clients->values(),
            'stats' => $stats,
            'filter' => $filter,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function summarize(Client $client): array
    {
        // Cashier guarda una fila por suscripción; la vigente es la última
        // creada que no terminó. Si todas terminaron, se muestra la más
        // reciente para saber por qué se fue el cliente.
        $subscription = $client->subscriptions
            ->sortByDesc('created_at')
            ->first(fn ($sub) => $sub->ends_at === null || $sub->ends_at->isFuture())
            ?? $client->subscriptions->sortByDesc('created_at')->first();

        $status = $subscription?->stripe_status;

        return [
            'id' => $client->id,
            'name' => $client->name,
            'contact_email' => $client->contact_email ?? $client->users->first()?->email,
            'users_count' => $client->users->count(),
            'plan_name' => $client->plan?->name ?? $this->planFromPrice($subscription?->stripe_price)?->name,
            'price_cents' => $client->plan?->price_cents
                ?? $this->planFromPrice($subscription?->stripe_price)?->price_cents
                ?? 0,
            'stripe_status' => $status,
            'stripe_id' => $client->stripe_id,
            'is_paying' => in_array($status, self::PAYING_STATUSES, true),
            'at_risk' => in_array($status, self::AT_RISK_STATUSES, true),
            'trial_ends_at' => $subscription?->trial_ends_at ?? $client->trial_ends_at,
            'ends_at' => $subscription?->ends_at,
            'created_at' => $client->created_at,
        ];
    }

    private function planFromPrice(?string $stripePrice): ?Plan
    {
        if ($stripePrice === null) {
            return null;
        }

        $this->plans ??= Plan::query()->get();

        return $this->plans->firstWhere('stripe_price_id', $stripePrice);
    }
}
