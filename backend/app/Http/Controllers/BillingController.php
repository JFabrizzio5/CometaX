<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Laravel\Cashier\Checkout;

/**
 * Portal de cliente: elegir plan y pagar.
 *
 *   domiciliar → suscripción recurrente (Stripe Checkout mode=subscription)
 *   unico      → cobro puntual del mes  (Stripe Checkout mode=payment)
 *
 * El estado real (plan asignado, factura pagada) lo confirma el webhook de
 * Stripe, no el retorno del navegador — el usuario puede cerrar la pestaña
 * antes de volver.
 */
class BillingController extends Controller
{
    public function index(): View
    {
        $plans = Plan::where('is_public', true)->orderBy('sort_order')->get();

        return view('billing.planes', [
            'plans' => $plans,
            'client' => auth()->user()->client,
        ]);
    }

    public function domiciliar(Plan $plan): Checkout
    {
        abort_unless($plan->is_public && $plan->stripe_price_id_recurring, 404);

        return auth()->user()->client
            ->newSubscription('default', $plan->stripe_price_id_recurring)
            ->checkout([
                'success_url' => route('billing.exito').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.cancelado'),
                'metadata' => ['plan_id' => $plan->id, 'modo' => 'domiciliado'],
            ]);
    }

    public function unico(Plan $plan): Checkout
    {
        abort_unless($plan->is_public && $plan->stripe_price_id_onetime, 404);

        return auth()->user()->client->checkout([$plan->stripe_price_id_onetime => 1], [
            'success_url' => route('billing.exito').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.cancelado'),
            'metadata' => ['plan_id' => $plan->id, 'modo' => 'unico'],
        ]);
    }

    /** Activa un plan gratuito (sin Stripe): solo asigna el plan al cliente. */
    public function activarGratis(Plan $plan): RedirectResponse
    {
        abort_unless($plan->is_public && $plan->price_cents === 0, 404);

        auth()->user()->client->forceFill(['plan_id' => $plan->id])->save();

        return redirect()->route('dashboard')
            ->with('status', "Plan «{$plan->name}» activado.");
    }

    public function exito(): View
    {
        return view('billing.exito');
    }

    public function cancelado(): RedirectResponse
    {
        return redirect()->route('billing.planes')
            ->with('status', 'Pago cancelado. No se te cobró nada.');
    }
}
