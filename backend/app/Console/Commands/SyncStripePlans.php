<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

/**
 * Crea (o completa) en Stripe el Product y los dos Price de cada plan público:
 *   - recurring: mensual, precio domiciliado (suscripción)
 *   - one-time:  precio de lista (pago único)
 *
 * Idempotente: solo crea lo que falta. Guarda los IDs en la tabla `plans`.
 * Corre en el servidor, que tiene STRIPE_SECRET en su config — el secreto
 * nunca sale de la máquina.
 *
 *   php artisan cometax:sync-stripe-plans
 */
class SyncStripePlans extends Command
{
    protected $signature = 'cometax:sync-stripe-plans {--currency=mxn}';

    protected $description = 'Crea/actualiza los Product y Price de Stripe para cada plan público';

    public function handle(): int
    {
        $currency = $this->option('currency');
        $stripe = Cashier::stripe();

        $plans = Plan::where('is_public', true)->get();

        if ($plans->isEmpty()) {
            $this->warn('No hay planes públicos. Corre el PlanSeeder primero.');

            return self::FAILURE;
        }

        foreach ($plans as $plan) {
            $this->line("→ {$plan->name} ({$plan->slug})");

            if ($plan->price_domiciliado_cents === null) {
                $this->warn('  sin price_domiciliado_cents, se omite.');
                continue;
            }

            // 1. Product
            if ($plan->stripe_product_id === null) {
                $product = $stripe->products->create([
                    'name' => "CometaX · {$plan->name}",
                    'metadata' => ['plan_slug' => $plan->slug],
                ]);
                $plan->stripe_product_id = $product->id;
                $this->info("  producto creado {$product->id}");
            }

            // 2. Price recurrente (domiciliado)
            if ($plan->stripe_price_id_recurring === null) {
                $price = $stripe->prices->create([
                    'product' => $plan->stripe_product_id,
                    'currency' => $currency,
                    'unit_amount' => $plan->price_domiciliado_cents,
                    'recurring' => ['interval' => 'month'],
                    'nickname' => "{$plan->slug} domiciliado",
                    'metadata' => ['plan_slug' => $plan->slug, 'modo' => 'domiciliado'],
                ]);
                $plan->stripe_price_id_recurring = $price->id;
                $this->info("  price recurrente {$price->id} ({$plan->priceDomiciliadoLabel()}/mes)");
            }

            // 3. Price one-time (pago único)
            if ($plan->stripe_price_id_onetime === null) {
                $price = $stripe->prices->create([
                    'product' => $plan->stripe_product_id,
                    'currency' => $currency,
                    'unit_amount' => $plan->price_cents,
                    'nickname' => "{$plan->slug} pago único",
                    'metadata' => ['plan_slug' => $plan->slug, 'modo' => 'unico'],
                ]);
                $plan->stripe_price_id_onetime = $price->id;
                $this->info("  price único {$price->id} ({$plan->priceOnetimeLabel()})");
            }

            $plan->save();
        }

        $this->newLine();
        $this->info('Planes sincronizados con Stripe.');

        return self::SUCCESS;
    }
}
