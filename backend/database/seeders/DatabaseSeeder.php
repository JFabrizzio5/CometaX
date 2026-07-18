<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Incident;
use App\Models\Plan;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $planPro = Plan::create([
            'name' => 'Plan Pro',
            'slug' => 'pro',
            'description' => '40 horas de soporte al mes, hasta 3 proyectos activos.',
            'price_cents' => 1850000,
            'included_hours' => 40,
            'hourly_overage_rate_cents' => 25000,
            'billing_cycle' => 'mensual',
            'max_clients' => 25,
            'is_public' => true,
            'sort_order' => 2,
        ]);

        Plan::create([
            'name' => 'Plan Starter',
            'slug' => 'starter',
            'description' => '15 horas de soporte al mes, 1 proyecto activo.',
            'price_cents' => 800000,
            'included_hours' => 15,
            'hourly_overage_rate_cents' => 30000,
            'billing_cycle' => 'mensual',
            'max_clients' => 40,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        Consultant::create([
            'name' => 'Jose (Mega Admin)',
            'email' => 'admin@cometax.click',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);

        $ana = Consultant::create([
            'name' => 'Ana Reyes',
            'title' => 'Consultora líder',
            'email' => 'ana@cometax.click',
            'password' => bcrypt('password'),
            'role' => 'consultant',
        ]);

        $client = Client::create([
            'name' => 'Constructora Del Valle',
            'slug' => 'constructora-del-valle',
            'contact_email' => 'admin@delvalle.mx',
            'plan_id' => $planPro->id,
        ]);

        User::create([
            'client_id' => $client->id,
            'name' => 'Cliente Demo',
            'email' => 'cliente@delvalle.mx',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'lead_consultant_id' => $ana->id,
            'name' => 'Rediseño Web Corporativo',
            'slug' => 'rediseno-web-corporativo',
            'description' => 'Sitio institucional + CMS',
            'status' => 'activo',
            'progress_percent' => 72,
            'hours_budgeted' => 25,
            'hours_used' => 18,
        ]);

        Incident::create([
            'project_id' => $project->id,
            'title' => 'Ajustar responsive en checkout',
            'priority' => 'media',
            'status' => 'revision',
            'assignee_consultant_id' => $ana->id,
        ]);
    }
}
