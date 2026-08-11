<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumenYFiltrosDeHorasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Consultant $joseph;

    private Consultant $angel;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::create(['name' => 'Cliente', 'slug' => 'cliente']);
        $this->project = Project::create([
            'client_id' => $client->id, 'name' => 'Traton', 'slug' => 'traton',
            'status' => 'activo', 'progress_percent' => 0, 'hours_budgeted' => 100, 'hours_used' => 0,
        ]);

        $this->joseph = Consultant::create(['name' => 'Joseph', 'email' => 'j@x.mx', 'password' => 'x', 'role' => 'super_admin']);
        $this->angel = Consultant::create(['name' => 'Angel', 'email' => 'a@x.mx', 'password' => 'x', 'role' => 'admin']);

        $sprint1 = $this->project->milestones()->create(['label' => 'Login', 'phase' => 'Sprint 1', 'sort_order' => 1, 'status' => 'done']);
        $sprint2 = $this->project->milestones()->create(['label' => 'Reportes', 'phase' => 'Sprint 2', 'sort_order' => 2, 'status' => 'in_progress']);

        $this->project->timeEntries()->createMany([
            ['entry_date' => '2026-07-10', 'activity' => 'Login backend', 'category' => 'backend', 'hours' => 8, 'consultant_id' => $this->joseph->id, 'milestone_id' => $sprint1->id, 'source' => 'medido'],
            ['entry_date' => '2026-07-12', 'activity' => 'Login UI', 'category' => 'frontend', 'hours' => 4, 'consultant_id' => $this->angel->id, 'milestone_id' => $sprint1->id, 'source' => 'medido'],
            ['entry_date' => '2026-08-05', 'activity' => 'Reportes backend', 'category' => 'backend', 'hours' => 6, 'consultant_id' => $this->joseph->id, 'milestone_id' => $sprint2->id, 'source' => 'reconstruido'],
            ['entry_date' => '2026-08-07', 'activity' => 'Prueba en staging', 'category' => 'qa_manual', 'hours' => 2, 'consultant_id' => $this->angel->id, 'milestone_id' => $sprint2->id, 'source' => 'reconstruido'],
        ]);
    }

    private function verProyecto(array $query = [])
    {
        return $this->actingAs($this->joseph, 'consultant')
            ->get(route('admin.projects.show', $this->project).($query ? '?'.http_build_query($query) : ''));
    }

    public function test_sin_filtro_muestra_todas_las_horas(): void
    {
        $resumen = $this->verProyecto()->assertOk()->viewData('resumen');

        $this->assertEquals(20.0, $resumen['total']);
        $this->assertSame(4, $resumen['renglones']);
    }

    public function test_filtra_por_rango_de_fechas(): void
    {
        $response = $this->verProyecto(['desde' => '2026-08-01', 'hasta' => '2026-08-31'])->assertOk();

        $this->assertEquals(8.0, $response->viewData('resumen')['total']);
        $this->assertCount(2, $response->viewData('entries'));

        // El total sin filtrar se conserva para poder decir "8 de 20".
        $this->assertEquals(20.0, $response->viewData('totalSinFiltrar'));
    }

    public function test_filtra_por_persona_y_por_tipo(): void
    {
        $this->assertEquals(14.0, $this->verProyecto(['quien' => $this->joseph->id])->viewData('resumen')['total']);
        $this->assertEquals(14.0, $this->verProyecto(['categoria' => 'backend'])->viewData('resumen')['total']);
    }

    public function test_filtra_por_hito(): void
    {
        $sprint1 = $this->project->milestones()->where('phase', 'Sprint 1')->first();

        $this->assertEquals(12.0, $this->verProyecto(['hito' => $sprint1->id])->viewData('resumen')['total']);
    }

    public function test_el_orden_cronologico_invierte_la_tabla(): void
    {
        $desc = $this->verProyecto()->viewData('entries');
        $asc = $this->verProyecto(['orden' => 'asc'])->viewData('entries');

        $this->assertSame('2026-08-07', $desc->first()->entry_date->toDateString());
        $this->assertSame('2026-07-10', $asc->first()->entry_date->toDateString());
    }

    public function test_el_analisis_agrupa_por_tipo_persona_y_fase(): void
    {
        $resumen = $this->verProyecto()->viewData('resumen');

        // Backend domina: 8 + 6 = 14 de 20 h.
        $this->assertSame('Backend', $resumen['por_categoria'][0]['etiqueta']);
        $this->assertEquals(14.0, $resumen['por_categoria'][0]['horas']);
        $this->assertEquals(70.0, $resumen['por_categoria'][0]['porcentaje']);

        $this->assertSame('Joseph', $resumen['por_persona'][0]['etiqueta']);
        $this->assertEquals(14.0, $resumen['por_persona'][0]['horas']);

        $fases = collect($resumen['por_fase'])->pluck('horas', 'etiqueta');
        $this->assertEquals(12.0, $fases['Sprint 1']);
        $this->assertEquals(8.0, $fases['Sprint 2']);
    }

    public function test_el_analisis_respeta_el_filtro_activo(): void
    {
        $resumen = $this->verProyecto(['desde' => '2026-08-01'])->viewData('resumen');

        // Dentro de agosto backend son 6 de 8 h, no 14 de 20.
        $this->assertEquals(6.0, $resumen['por_categoria'][0]['horas']);
        $this->assertEquals(75.0, $resumen['por_categoria'][0]['porcentaje']);
    }

    public function test_guarda_fase_y_fechas_de_un_hito(): void
    {
        $this->actingAs($this->joseph, 'consultant')
            ->post(route('admin.projects.milestones.store', $this->project), [
                'label' => 'Facturación',
                'phase' => 'Sprint 3',
                'starts_on' => '2026-09-01',
                'due_on' => '2026-09-15',
                'hours_budgeted' => 30,
            ])->assertRedirect();

        $hito = $this->project->milestones()->where('label', 'Facturación')->sole();

        $this->assertSame('Sprint 3', $hito->phase);
        $this->assertSame('2026-09-01', $hito->starts_on->toDateString());
        $this->assertSame('2026-09-15', $hito->due_on->toDateString());
        $this->assertEquals(30.0, (float) $hito->hours_budgeted);
    }

    public function test_rechaza_un_hito_que_termina_antes_de_empezar(): void
    {
        $this->actingAs($this->joseph, 'consultant')
            ->post(route('admin.projects.milestones.store', $this->project), [
                'label' => 'Imposible', 'starts_on' => '2026-09-15', 'due_on' => '2026-09-01',
            ])->assertSessionHasErrors('due_on');
    }

    public function test_edita_las_fechas_de_un_hito_existente(): void
    {
        $hito = $this->project->milestones()->first();

        $this->actingAs($this->joseph, 'consultant')
            ->put(route('admin.milestones.update', $hito), [
                'status' => 'done', 'label' => 'Login', 'phase' => 'Sprint 1',
                'starts_on' => '2026-07-01', 'due_on' => '2026-07-15', 'hours_budgeted' => 12,
            ])->assertRedirect();

        $hito->refresh();
        $this->assertSame('2026-07-01', $hito->starts_on->toDateString());
        $this->assertEquals(12.0, (float) $hito->hours_budgeted);
    }

    public function test_el_cambio_rapido_de_estado_no_borra_las_fechas(): void
    {
        $hito = $this->project->milestones()->first();
        $hito->update(['starts_on' => '2026-07-01', 'due_on' => '2026-07-15']);

        // El selector del tablero manda solo `status`; el resto debe quedarse.
        $this->actingAs($this->joseph, 'consultant')
            ->put(route('admin.milestones.update', $hito), ['status' => 'in_progress'])
            ->assertRedirect();

        $hito->refresh();
        $this->assertSame('in_progress', $hito->status);
        $this->assertSame('2026-07-01', $hito->starts_on->toDateString());
        $this->assertSame('Login', $hito->label);
    }
}
