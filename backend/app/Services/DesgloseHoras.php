<?php

namespace App\Services;

use App\Models\Consultant;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reconstruye el desglose de horas de un trabajo que sí se hizo pero que nadie
 * alcanzó a registrar en el momento.
 *
 * No inventa trabajo: parte de lo que el admin declara (qué se entregó, de qué
 * tipo fue y cuántas horas costó aprox.) y lo reparte en actividades con la
 * forma habitual de ese tipo de entrega. La propuesta se revisa y se edita en
 * pantalla antes de guardarse; nada se persiste desde aquí.
 *
 * Las categorías qa_manual y qa_automatizado NO son intercambiables: describen
 * quién ejecutó la prueba y el reporte al cliente se apoya en esa distinción.
 */
class DesgloseHoras
{
    /**
     * Reparto por tipo de entrega. Suman 1.0; las que quedan en cero tras el
     * redondeo se descartan.
     */
    private const REPARTOS = [
        'modulo_nuevo' => [
            'analisis' => 0.15, 'backend' => 0.38, 'frontend' => 0.20,
            'qa_manual' => 0.12, 'despliegue' => 0.08, 'reunion' => 0.07,
        ],
        'ajuste' => [
            'analisis' => 0.10, 'backend' => 0.45, 'frontend' => 0.25,
            'qa_manual' => 0.15, 'despliegue' => 0.05,
        ],
        'correccion' => [
            'analisis' => 0.20, 'backend' => 0.45, 'qa_manual' => 0.25, 'despliegue' => 0.10,
        ],
        'integracion' => [
            'analisis' => 0.20, 'backend' => 0.45, 'qa_manual' => 0.20, 'despliegue' => 0.15,
        ],
        'despliegue' => [
            'despliegue' => 0.60, 'qa_manual' => 0.25, 'backend' => 0.15,
        ],
        'investigacion' => [
            'analisis' => 0.70, 'backend' => 0.20, 'reunion' => 0.10,
        ],
    ];

    public const TIPOS = [
        'modulo_nuevo' => 'Módulo o funcionalidad nueva',
        'ajuste' => 'Ajuste sobre algo existente',
        'correccion' => 'Corrección de un defecto',
        'integracion' => 'Integración con un tercero',
        'despliegue' => 'Despliegue / puesta en producción',
        'investigacion' => 'Investigación o análisis',
    ];

    /**
     * Redacción de cada renglón. {t} es el título del trabajo.
     */
    private const PLANTILLAS = [
        'analisis' => 'Análisis y diseño de la solución: {t}',
        'backend' => 'Implementación de backend: {t}',
        'frontend' => 'Implementación de interfaz: {t}',
        'qa_manual' => 'Verificación manual en staging: {t}',
        'qa_automatizado' => 'Ejecución de la suite automatizada sobre {t}',
        'despliegue' => 'Despliegue y prueba de humo en producción: {t}',
        'reunion' => 'Seguimiento y coordinación: {t}',
        'soporte' => 'Atención de incidencias relacionadas: {t}',
    ];

    /** Perfil preferente por categoría, contra el role_label del consultor. */
    private const AFINIDAD = [
        'backend' => ['backend', 'full', 'dev', 'desarroll'],
        'frontend' => ['front', 'full', 'ui', 'dise'],
        'qa_manual' => ['qa', 'test', 'calidad'],
        'qa_automatizado' => ['qa', 'test', 'calidad'],
        'analisis' => ['lider', 'líder', 'analis', 'arqui', 'pm'],
        'despliegue' => ['devops', 'infra', 'backend'],
        'reunion' => ['lider', 'líder', 'pm', 'cuenta'],
    ];

    /**
     * @param  array{titulo:string,tipo:string,horas:float,fecha:string,fecha_fin?:?string,milestone_id?:?int,participantes?:array<int>,incluir_qa_automatizado?:bool}  $datos
     * @return Collection<int, array<string, mixed>> renglones listos para revisar, sin guardar
     */
    public function proponer(Project $project, array $datos): Collection
    {
        $reparto = self::REPARTOS[$datos['tipo']] ?? self::REPARTOS['modulo_nuevo'];

        // El QA automatizado no se reparte por peso: es tiempo de máquina, casi
        // constante. Se agrega aparte y se descuenta del resto para no inflar el total.
        $horasAuto = ! empty($datos['incluir_qa_automatizado']) ? 0.25 : 0.0;
        $horasHumanas = max(0.25, round($datos['horas'] - $horasAuto, 2));

        $consultores = $this->consultoresDisponibles($project, $datos['participantes'] ?? []);
        $fechas = $this->calendario($datos['fecha'], $datos['fecha_fin'] ?? null, count($reparto));

        $renglones = collect();
        $i = 0;

        foreach ($this->repartirHoras($horasHumanas, $reparto) as $categoria => $horas) {
            $renglones->push([
                'entry_date' => $fechas[$i] ?? $fechas[count($fechas) - 1],
                'category' => $categoria,
                'activity' => str_replace('{t}', $datos['titulo'], self::PLANTILLAS[$categoria] ?? '{t}'),
                'hours' => $horas,
                'consultant_id' => $this->asignar($categoria, $consultores),
                'milestone_id' => $datos['milestone_id'] ?? null,
                'billable' => true,
            ]);
            $i++;
        }

        if ($horasAuto > 0) {
            $renglones->push([
                'entry_date' => $fechas[count($fechas) - 1],
                'category' => 'qa_automatizado',
                'activity' => str_replace('{t}', $datos['titulo'], self::PLANTILLAS['qa_automatizado']),
                'hours' => $horasAuto,
                'consultant_id' => $this->asignar('qa_automatizado', $consultores),
                'milestone_id' => $datos['milestone_id'] ?? null,
                'billable' => true,
            ]);
        }

        return $renglones;
    }

    /**
     * Reparte el total en múltiplos de 0.25 h respetando la suma exacta: el
     * sobrante del redondeo se ajusta contra el renglón más grande.
     *
     * @param  array<string, float>  $reparto
     * @return array<string, float>
     */
    private function repartirHoras(float $total, array $reparto): array
    {
        $horas = [];

        foreach ($reparto as $categoria => $peso) {
            $valor = round(($total * $peso) / 0.25) * 0.25;
            if ($valor > 0) {
                $horas[$categoria] = $valor;
            }
        }

        if ($horas === []) {
            return [array_key_first($reparto) => $total];
        }

        $diferencia = round($total - array_sum($horas), 2);

        if (abs($diferencia) >= 0.01) {
            $mayor = array_search(max($horas), $horas, true);
            $horas[$mayor] = round($horas[$mayor] + $diferencia, 2);

            // Un ajuste negativo grande no puede dejar el renglón en cero o menos.
            if ($horas[$mayor] <= 0) {
                unset($horas[$mayor]);
            }
        }

        return $horas;
    }

    /**
     * Fechas para repartir los renglones. Con rango, las distribuye; sin rango,
     * todas caen el mismo día.
     *
     * @return array<int, string>
     */
    private function calendario(string $desde, ?string $hasta, int $cuantos): array
    {
        $inicio = Carbon::parse($desde)->startOfDay();

        if (! $hasta || $cuantos < 2) {
            return array_fill(0, max(1, $cuantos), $inicio->toDateString());
        }

        $fin = Carbon::parse($hasta)->startOfDay();
        $dias = max(0, $inicio->diffInDays($fin));

        $fechas = [];
        for ($i = 0; $i < $cuantos; $i++) {
            $offset = (int) round(($dias * $i) / max(1, $cuantos - 1));
            $fechas[] = $inicio->copy()->addDays($offset)->toDateString();
        }

        return $fechas;
    }

    /**
     * @param  array<int>  $seleccionados
     * @return Collection<int, Consultant>
     */
    private function consultoresDisponibles(Project $project, array $seleccionados): Collection
    {
        $equipo = $project->consultants;

        if ($seleccionados !== []) {
            $equipo = $equipo->whereIn('id', $seleccionados);
        }

        if ($equipo->isEmpty() && $project->lead_consultant_id) {
            $equipo = collect([$project->leadConsultant])->filter();
        }

        return $equipo->values();
    }

    /**
     * Elige quién hizo esa categoría: primero por afinidad con su role_label en
     * el proyecto, si no el líder, si no nadie (queda para asignar a mano).
     *
     * @param  Collection<int, Consultant>  $consultores
     */
    private function asignar(string $categoria, Collection $consultores): ?int
    {
        if ($consultores->isEmpty()) {
            return null;
        }

        $pistas = self::AFINIDAD[$categoria] ?? [];

        $afin = $consultores->first(function (Consultant $c) use ($pistas) {
            // El líder entra sin pivot cuando el proyecto no tiene equipo asignado.
            $etiqueta = mb_strtolower((string) ($c->pivot?->role_label ?? $c->title ?? ''));

            foreach ($pistas as $pista) {
                if ($etiqueta !== '' && str_contains($etiqueta, $pista)) {
                    return true;
                }
            }

            return false;
        });

        return $afin?->id ?? $consultores->first()->id;
    }

    /**
     * Etiqueta legible de una categoría, para la pantalla de revisión.
     */
    public static function categoria(string $clave): string
    {
        return TimeEntry::CATEGORIAS[$clave] ?? $clave;
    }
}
