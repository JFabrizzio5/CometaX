<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\ResumenHoras;
use App\Support\Export\PdfWriter;
use App\Support\Export\XlsxWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reporte de horas de un proyecto en Excel y PDF.
 *
 * Enfocado al cliente: no muestra el origen de la hora (medido/reconstruido) ni
 * lo no facturable. Esa distinción es control interno y vive en el panel.
 *
 * Usa los mismos filtros de query string que la tabla del panel, así que lo que
 * el admin ve filtrado es exactamente lo que se descarga.
 */
class ReporteHorasController extends Controller
{
    public function __construct(private readonly ResumenHoras $resumen) {}

    public function excel(Request $request, Project $project): Response
    {
        [$entries, $resumen, $periodo] = $this->datos($request, $project);

        $xlsx = new XlsxWriter;

        $xlsx->agregarHoja('Resumen', ['Concepto', 'Valor'], [
            ['Cliente', $project->client->name],
            ['Proyecto', $project->name],
            ['Periodo', $periodo],
            ['Avance reportado', $project->progress_percent.'%'],
            ['Horas del periodo', round($resumen['total'], 2)],
            ['Horas presupuestadas', round((float) $project->hours_budgeted, 2)],
            ['Actividades registradas', $resumen['renglones']],
            ['Generado', Carbon::now()->format('d/m/Y H:i')],
        ], [28, 46]);

        $xlsx->agregarHoja('Por fase', ['Fase / sprint', 'Horas', '% del periodo'],
            $this->filasAgrupadas($resumen['por_fase']), [40, 14, 16]);

        $xlsx->agregarHoja('Por tipo de trabajo', ['Tipo', 'Horas', '% del periodo'],
            $this->filasAgrupadas($resumen['por_categoria']), [32, 14, 16]);

        $xlsx->agregarHoja('Detalle', ['Fecha', 'Fase / sprint', 'Hito', 'Actividad', 'Tipo', 'Responsable', 'Horas'],
            $entries->map(fn (TimeEntry $e) => [
                $e->entry_date->format('d/m/Y'),
                $e->milestone?->phase ?: '—',
                $e->milestone?->label ?: '—',
                $e->activity,
                $e->categoriaLegible(),
                $e->consultant?->name ?: '—',
                round((float) $e->hours, 2),
            ])->all(), [13, 24, 30, 52, 20, 22, 10]);

        return $this->descarga(
            $xlsx->generar(),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->nombre($project, $periodo, 'xlsx'),
        );
    }

    public function pdf(Request $request, Project $project): Response
    {
        [$entries, $resumen, $periodo] = $this->datos($request, $project);

        $pdf = new PdfWriter(horizontal: true);

        $pdf->titulo('Reporte de horas — '.$project->name)
            ->parrafo($project->client->name.'   ·   Periodo: '.$periodo.'   ·   Generado el '.Carbon::now()->format('d/m/Y'))
            ->parrafo('Total del periodo: '.number_format($resumen['total'], 2).' h en '.$resumen['renglones'].' actividades')
            ->espacio(8);

        if ($resumen['por_fase'] !== []) {
            $pdf->subtitulo('Distribución por fase / sprint')
                ->tabla(['Fase / sprint', 'Horas', '% del periodo'],
                    $this->filasAgrupadas($resumen['por_fase']),
                    [50, 12, 14], ['izq', 'der', 'der'])
                ->espacio(6);
        }

        if ($resumen['por_categoria'] !== []) {
            $pdf->subtitulo('Distribución por tipo de trabajo')
                ->tabla(['Tipo de trabajo', 'Horas', '% del periodo'],
                    $this->filasAgrupadas($resumen['por_categoria']),
                    [50, 12, 14], ['izq', 'der', 'der'])
                ->espacio(6);
        }

        $pdf->subtitulo('Detalle de actividades');

        if ($entries->isEmpty()) {
            $pdf->parrafo('No hay horas registradas en el periodo seleccionado.');
        } else {
            $pdf->tabla(
                ['Fecha', 'Fase / sprint', 'Hito', 'Actividad', 'Tipo', 'Responsable', 'Horas'],
                $entries->map(fn (TimeEntry $e) => [
                    $e->entry_date->format('d/m/Y'),
                    $e->milestone?->phase ?: '—',
                    $e->milestone?->label ?: '—',
                    $e->activity,
                    $e->categoriaLegible(),
                    $e->consultant?->name ?: '—',
                    number_format((float) $e->hours, 2),
                ])->all(),
                [10, 16, 20, 34, 14, 16, 7],
                ['izq', 'izq', 'izq', 'izq', 'izq', 'izq', 'der'],
            );
        }

        return $this->descarga($pdf->generar(), 'application/pdf', $this->nombre($project, $periodo, 'pdf'));
    }

    /**
     * @return array{0: Collection<int, TimeEntry>, 1: array<string, mixed>, 2: string}
     */
    private function datos(Request $request, Project $project): array
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $entries = $project->timeEntries()
            ->with('consultant', 'milestone')
            ->enRango($desde, $hasta)
            ->when($request->query('hito'), fn ($q, $v) => $q->where('milestone_id', $v))
            ->when($request->query('categoria'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->query('quien'), fn ($q, $v) => $q->where('consultant_id', $v))
            // El reporte va siempre cronológico: el cliente lo lee como bitácora.
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        return [$entries, $this->resumen->de($entries), $this->periodo($desde, $hasta, $entries)];
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     */
    private function periodo(?string $desde, ?string $hasta, Collection $entries): string
    {
        if ($desde || $hasta) {
            return ($desde ? Carbon::parse($desde)->format('d/m/Y') : 'inicio')
                .' — '.($hasta ? Carbon::parse($hasta)->format('d/m/Y') : 'hoy');
        }

        if ($entries->isEmpty()) {
            return 'sin registros';
        }

        return $entries->first()->entry_date->format('d/m/Y').' — '.$entries->last()->entry_date->format('d/m/Y');
    }

    /**
     * @param  array<int, array{etiqueta:string, horas:float, porcentaje:float, renglones:int}>  $grupos
     * @return array<int, array<int, mixed>>
     */
    private function filasAgrupadas(array $grupos): array
    {
        return array_map(fn (array $g) => [$g['etiqueta'], round($g['horas'], 2), $g['porcentaje'].'%'], $grupos);
    }

    private function nombre(Project $project, string $periodo, string $extension): string
    {
        $slug = $project->slug ?: 'proyecto';

        return 'reporte-horas-'.$slug.'-'.Carbon::now()->format('Ymd').'.'.$extension;
    }

    /**
     * RFC 5987 en el filename*: sin eso los acentos del nombre llegan rotos.
     */
    private function descarga(string $contenido, string $tipo, string $nombre): Response
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre) ?? 'reporte';

        return response($contenido, 200, [
            'Content-Type' => $tipo,
            'Content-Length' => (string) strlen($contenido),
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $ascii,
                rawurlencode($nombre),
            ),
        ]);
    }
}
