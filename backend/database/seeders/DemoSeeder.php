<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Tenant demo para mostrar el portal de cliente sin Google.
 * Idempotente. Se activa el acceso con DEMO_LOGIN=true (ver DemoController).
 *
 * Requiere PlanSeeder corrido antes (usa el plan 'basico'); si no existe,
 * el cliente queda sin plan y el dashboard muestra "Sin plan asignado".
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $basico = Plan::where('slug', 'basico')->first();

        $client = Client::updateOrCreate(
            ['slug' => 'demo-cometax'],
            [
                'name' => 'Empresa Demo',
                'contact_email' => 'demo@cometax.click',
                'plan_id' => $basico?->id,
            ],
        );

        User::updateOrCreate(
            ['email' => 'demo@cometax.click'],
            [
                'client_id' => $client->id,
                'name' => 'Cuenta Demo',
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
