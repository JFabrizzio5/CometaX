<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroDeHorasTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): Consultant
    {
        return Consultant::create([
            'name' => 'Joseph', 'title' => 'Backend', 'email' => 'staff@cometax.mx',
            'password' => 'secreto123', 'role' => 'super_admin',
        ]);
    }

    /** clients.slug es unique: cada proyecto de prueba necesita su propio cliente. */
    private function proyecto(): Project
    {
        $n = Client::count() + 1;
        $client = Client::create(['name' => "Cliente {$n}", 'slug' => "cliente-{$n}"]);

        return Project::create([
            'client_id' => $client->id, 'name' => "Panel {$n}", 'slug' => "panel-{$n}",
            'status' => 'activo', 'progress_percent' => 0,
            'hours_budgeted' => 100, 'hours_used' => 0,
        ]);
    }

    public function test_registra_horas_y_recalcula_el_total_del_proyecto(): void
    {
        $project = $this->proyecto();

        $this->actingAs($this->staff(), 'consultant')
            ->post(route('admin.time.store', $project), [
                'entry_date' => '2026-08-03',
                'activity' => 'Implementación del exportador',
                'category' => 'backend',
                'hours' => 4.5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'project_id' => $project->id,
            'activity' => 'Implementación del exportador',
            'source' => 'medido',
        ]);

        // hours_used deja de teclearse a mano: sale de la suma.
        $this->assertEquals(4.5, (float) $project->fresh()->hours_used);
    }

    public function test_la_propuesta_no_guarda_nada_hasta_confirmar(): void
    {
        $project = $this->proyecto();

        $this->actingAs($this->staff(), 'consultant')
            ->from(route('admin.projects.show', $project))
            ->post(route('admin.time.propose', $project), [
                'titulo' => 'Exportador de reportes',
                'tipo' => 'modulo_nuevo',
                'horas' => 12,
                'fecha' => '2026-08-03',
            ])
            ->assertRedirect(route('admin.projects.show', $project))
            ->assertSessionHas('desglose');

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_confirmar_guarda_el_lote_como_reconstruido_y_se_puede_deshacer(): void
    {
        $project = $this->proyecto();
        $staff = $this->staff();

        $this->actingAs($staff, 'consultant')
            ->post(route('admin.time.confirm', $project), [
                'renglones' => [
                    ['entry_date' => '2026-08-03', 'activity' => 'Análisis', 'category' => 'analisis', 'hours' => 2],
                    ['entry_date' => '2026-08-04', 'activity' => 'Backend', 'category' => 'backend', 'hours' => 6],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('time_entries', 2);
        $this->assertEquals(8.0, (float) $project->fresh()->hours_used);

        $batch = TimeEntry::first()->batch_id;
        $this->assertNotNull($batch);
        $this->assertSame('reconstruido', TimeEntry::first()->source);

        $this->actingAs($staff, 'consultant')
            ->delete(route('admin.time.batch.destroy', [$project, $batch]))
            ->assertRedirect();

        $this->assertDatabaseCount('time_entries', 0);
        $this->assertEquals(0.0, (float) $project->fresh()->hours_used);
    }

    public function test_rechaza_una_categoria_inventada(): void
    {
        $project = $this->proyecto();

        $this->actingAs($this->staff(), 'consultant')
            ->post(route('admin.time.store', $project), [
                'entry_date' => '2026-08-03',
                'activity' => 'Algo',
                'category' => 'categoria_que_no_existe',
                'hours' => 1,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_no_acepta_un_hito_de_otro_proyecto(): void
    {
        $project = $this->proyecto();
        $otro = $this->proyecto();
        $hitoAjeno = $otro->milestones()->create(['label' => 'Ajeno', 'sort_order' => 1, 'status' => 'pending']);

        $this->actingAs($this->staff(), 'consultant')
            ->post(route('admin.time.store', $project), [
                'entry_date' => '2026-08-03',
                'activity' => 'Algo',
                'category' => 'backend',
                'hours' => 1,
                'milestone_id' => $hitoAjeno->id,
            ])
            ->assertSessionHasErrors('milestone_id');
    }

    public function test_un_cliente_no_entra_al_registro_de_horas(): void
    {
        $project = $this->proyecto();

        $this->post(route('admin.time.store', $project), [
            'entry_date' => '2026-08-03', 'activity' => 'x', 'category' => 'backend', 'hours' => 1,
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_la_vista_del_proyecto_muestra_las_horas_registradas(): void
    {
        $project = $this->proyecto();
        $project->timeEntries()->create([
            'entry_date' => '2026-08-03', 'activity' => 'Implementación del exportador',
            'category' => 'backend', 'hours' => 4.5, 'source' => 'medido',
        ]);

        $this->actingAs($this->staff(), 'consultant')
            ->get(route('admin.projects.show', $project))
            ->assertOk()
            ->assertSee('Implementación del exportador')
            ->assertSee('Registro de horas');
    }
}
