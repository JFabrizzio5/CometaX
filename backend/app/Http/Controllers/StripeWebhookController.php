<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook de Stripe. Extiende el de Cashier (que mantiene la tabla
 * `subscriptions` al día) y agrega la lógica propia de CometaX:
 *
 *   - suscripción creada/actualizada → asigna el plan al Client.
 *   - checkout de pago único completado → registra la factura pagada del mes.
 *
 * La firma la valida el middleware VerifyWebhookSignature con
 * STRIPE_WEBHOOK_SECRET; aquí ya llega verificado.
 */
class StripeWebhookController extends CashierController
{
    public function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        $this->asignarPlanDesdeSuscripcion($payload);

        return $response;
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        $this->asignarPlanDesdeSuscripcion($payload);

        return $response;
    }

    /**
     * Pago único: Stripe manda checkout.session.completed con mode=payment.
     * Cashier no lo maneja, así que lo registramos nosotros.
     */
    public function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        if (($session['mode'] ?? null) !== 'payment') {
            return $this->successMethod(); // las de suscripción las cubre Cashier
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            return $this->successMethod();
        }

        $client = $this->clientDesde($session['customer'] ?? null);
        $plan = $this->planDesdeId($session['metadata']['plan_id'] ?? null);

        if ($client === null || $plan === null) {
            Log::warning('checkout.session.completed sin client/plan resoluble', [
                'customer' => $session['customer'] ?? null,
                'metadata' => $session['metadata'] ?? null,
            ]);

            return $this->successMethod();
        }

        // Evita duplicar si Stripe reintenta el webhook.
        Invoice::firstOrCreate(
            ['stripe_invoice_id' => $session['id']],
            [
                'client_id' => $client->id,
                'plan_id' => $plan->id,
                'concept' => "Pago único · {$plan->name}",
                'amount_cents' => $session['amount_total'] ?? $plan->price_cents,
                'currency' => $session['currency'] ?? 'mxn',
                'status' => 'pagado',
                'paid_at' => now(),
                'invoice_date' => Carbon::now()->toDateString(),
            ],
        );

        if ($client->plan_id !== $plan->id) {
            $client->forceFill(['plan_id' => $plan->id])->save();
        }

        return $this->successMethod();
    }

    private function asignarPlanDesdeSuscripcion(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $client = $this->clientDesde($subscription['customer'] ?? null);

        if ($client === null) {
            return;
        }

        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if ($priceId === null) {
            return;
        }

        $plan = Plan::where('stripe_price_id_recurring', $priceId)->first();

        if ($plan !== null && $client->plan_id !== $plan->id) {
            $client->forceFill(['plan_id' => $plan->id])->save();
        }
    }

    private function clientDesde(?string $stripeCustomerId): ?Client
    {
        if ($stripeCustomerId === null) {
            return null;
        }

        return Cashier::findBillable($stripeCustomerId);
    }

    private function planDesdeId($planId): ?Plan
    {
        return $planId ? Plan::find($planId) : null;
    }
}
