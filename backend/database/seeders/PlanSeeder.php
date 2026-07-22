<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes comerciales reales (catálogo 2026). Precios en centavos MXN.
 *
 *   price_cents             = pago único (precio de lista, el de la landing)
 *   price_domiciliado_cents = suscripción domiciliada (precio preferente)
 *
 * Idempotente: se puede correr en cada deploy sin duplicar.
 * NOTA: included_hours y hourly_overage_rate_cents salen de la landing (40h/80h);
 * confirmar la tarifa de hora extra con negocio si cambia.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'basico',
                'name' => 'Plan Básico',
                'description' => '40 horas de equipo al mes.',
                'price_cents' => 2_800_000,            // $28,000 pago único
                'price_domiciliado_cents' => 2_600_000, // $26,000 domiciliado
                'included_hours' => 40,
                'hourly_overage_rate_cents' => 70_000,  // $700/h — confirmar
                'max_clients' => 40,
                'sort_order' => 1,
            ],
            [
                'slug' => 'pro',
                'name' => 'Plan Pro',
                'description' => '80 horas de equipo al mes.',
                'price_cents' => 5_200_000,            // $52,000 pago único
                'price_domiciliado_cents' => 5_000_000, // $50,000 domiciliado
                'included_hours' => 80,
                'hourly_overage_rate_cents' => 65_000,  // $650/h — confirmar
                'max_clients' => 25,
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, [
                    'billing_cycle' => 'mensual',
                    'status' => 'activo',
                    'is_public' => true,
                ]),
            );
        }
    }
}
