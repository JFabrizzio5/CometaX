<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Consultant;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ReporteHorasTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    private Consultant $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $client = Client::create(['name' => 'Cliente Acentuado ñ', 'slug' => 'cliente']);
        $this->project = Project::create([
            'client_id' => $client->id, 'name' => 'Integración ERP', 'slug' => 'integracion-erp',
            'status' => 'activo', 'progress_percent' => 40, 'hours_budgeted' => 80, 'hours_used' => 0,
        ]);

        $this->staff = Consultant::create(['name' => 'José Ángel', 'email' => 's@x.mx', 'password' => 'x', 'role' => 'super_admin']);

        $hito = $this->project->milestones()->create(['label' => 'Motor de reportes', 'phase' => 'Sprint 2', 'sort_order' => 1, 'status' => 'in_progress']);

        $this->project->timeEntries()->createMany([
            ['entry_date' => '2026-07-10', 'activity' => 'Análisis y diseño de la solución', 'category' => 'analisis', 'hours' => 3, 'consultant_id' => $this->staff->id, 'milestone_id' => $hito->id, 'source' => 'medido'],
            ['entry_date' => '2026-08-05', 'activity' => 'Implementación de backend', 'category' => 'backend', 'hours' => 9, 'consultant_id' => $this->staff->id, 'milestone_id' => $hito->id, 'source' => 'reconstruido'],
            ['entry_date' => '2026-08-07', 'activity' => 'Verificación manual en staging', 'category' => 'qa_manual', 'hours' => 1.5, 'consultant_id' => $this->staff->id, 'milestone_id' => $hito->id, 'source' => 'reconstruido'],
        ]);
    }

    public function test_el_excel_es_un_xlsx_valido_y_abre(): void
    {
        $response = $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.excel', $this->project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $ruta = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
        file_put_contents($ruta, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($ruta) === true, 'El .xlsx no es un ZIP legible.');

        // Las partes que Excel exige para no declarar el archivo corrupto.
        foreach (['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml'] as $parte) {
            $this->assertNotFalse($zip->locateName($parte), "Falta la parte {$parte}");
        }

        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet4.xml'), 'Faltan hojas: se esperaban 4.');

        // Todos los XML deben parsear; un carácter suelto rompe Excel sin avisar.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);
            if (str_ends_with($nombre, '.xml') || str_ends_with($nombre, '.rels')) {
                $this->assertInstanceOf(\SimpleXMLElement::class, simplexml_load_string($zip->getFromName($nombre)), "XML inválido en {$nombre}");
            }
        }

        $detalle = $zip->getFromName('xl/worksheets/sheet4.xml');
        $this->assertStringContainsString('Verificación manual en staging', $detalle);
        $this->assertStringContainsString('<v>9</v>', $detalle, 'Las horas deben ir como número, no como texto.');

        $zip->close();
        unlink($ruta);
    }

    public function test_el_excel_no_filtra_el_origen_interno_al_cliente(): void
    {
        $contenido = $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.excel', $this->project))
            ->getContent();

        $this->assertStringNotContainsString('reconstruido', $contenido);
    }

    public function test_el_pdf_tiene_estructura_valida(): void
    {
        $contenido = $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.pdf', $this->project))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->getContent();

        $this->assertStringStartsWith('%PDF-1.4', $contenido);
        $this->assertStringEndsWith('%%EOF', $contenido);
        $this->assertStringContainsString('/Type /Catalog', $contenido);
        $this->assertStringContainsString('trailer', $contenido);

        // La tabla xref debe tener una entrada por objeto o el lector no abre.
        preg_match('/xref\s+0 (\d+)/', $contenido, $m);
        $this->assertNotEmpty($m, 'No hay tabla xref.');
        $declarados = (int) $m[1];
        $this->assertSame($declarados - 1, preg_match_all('/^\d+ 0 obj$/m', $contenido), 'xref no cuadra con los objetos.');

        // startxref debe apuntar exactamente al inicio de la tabla.
        preg_match('/startxref\s+(\d+)/', $contenido, $sx);
        $this->assertSame('xref', substr($contenido, (int) $sx[1], 4));
    }

    public function test_el_pdf_escribe_acentos_en_cp1252(): void
    {
        $contenido = $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.pdf', $this->project))
            ->getContent();

        $this->assertStringContainsString('/WinAnsiEncoding', $contenido);
        // "Integración" en CP1252: la ó es 0xF3, no la secuencia UTF-8 de dos bytes.
        $this->assertStringContainsString(mb_convert_encoding('Integración', 'CP1252', 'UTF-8'), $contenido);
        $this->assertStringNotContainsString('Integraci?n', $contenido);
    }

    public function test_los_reportes_respetan_el_filtro_de_fechas(): void
    {
        $url = route('admin.projects.report.excel', [$this->project, 'desde' => '2026-08-01']);
        $contenido = $this->actingAs($this->staff, 'consultant')->get($url)->getContent();

        $ruta = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
        file_put_contents($ruta, $contenido);
        $zip = new ZipArchive;
        $zip->open($ruta);
        $detalle = $zip->getFromName('xl/worksheets/sheet4.xml');
        $zip->close();
        unlink($ruta);

        $this->assertStringContainsString('Implementación de backend', $detalle);
        $this->assertStringNotContainsString('Análisis y diseño', $detalle, 'Julio quedó fuera del filtro y no debe aparecer.');
    }

    public function test_el_reporte_no_truena_sin_horas(): void
    {
        $this->project->timeEntries()->delete();

        $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.pdf', $this->project))->assertOk();
        $this->actingAs($this->staff, 'consultant')
            ->get(route('admin.projects.report.excel', $this->project))->assertOk();
    }

    public function test_un_cliente_no_puede_descargar_el_reporte(): void
    {
        $this->get(route('admin.projects.report.excel', $this->project))
            ->assertRedirect(route('admin.login'));
    }
}
