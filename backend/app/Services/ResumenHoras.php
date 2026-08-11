<?php

namespace App\Services;

use App\Models\TimeEntry;
use Illuminate\Support\Collection;

/**
 * Agregados del registro de horas: en qué se gasta el tiempo, quién lo gasta y
 * contra qué hito. Trabaja sobre la colección ya filtrada, no vuelve a la base,
 * para que los totales siempre coincidan con lo que se está viendo en pantalla.
 */
class ResumenHoras
{
    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<string, mixed>
     */
    public function de(Collection $entries): array
    {
        $total = (float) $entries->sum('hours');

        return [
            'total' => $total,
            'renglones' => $entries->count(),
            'facturables' => (float) $entries->where('billable', true)->sum('hours'),
            'por_categoria' => $this->agrupar(
                $entries,
                fn (TimeEntry $e) => $e->category,
                fn (string $clave) => TimeEntry::CATEGORIAS[$clave] ?? $clave,
                $total,
            ),
            'por_persona' => $this->agrupar(
                $entries,
                fn (TimeEntry $e) => (string) ($e->consultant_id ?? '0'),
                fn (string $clave, Collection $grupo) => $grupo->first()->consultant?->name ?? 'Sin asignar',
                $total,
            ),
            'por_hito' => $this->agrupar(
                $entries,
                fn (TimeEntry $e) => (string) ($e->milestone_id ?? '0'),
                fn (string $clave, Collection $grupo) => $grupo->first()->milestone?->label ?? 'Sin hito',
                $total,
            ),
            'por_fase' => $this->agrupar(
                $entries,
                fn (TimeEntry $e) => $e->milestone?->phase ?: 'Sin fase',
                fn (string $clave) => $clave,
                $total,
            ),
        ];
    }

    /**
     * Agrupa, suma y ordena de mayor a menor con su porcentaje del total.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<int, array{etiqueta:string, horas:float, porcentaje:float, renglones:int}>
     */
    private function agrupar(Collection $entries, callable $clave, callable $etiqueta, float $total): array
    {
        return $entries
            ->groupBy($clave)
            ->map(function (Collection $grupo, $clave) use ($etiqueta, $total) {
                $horas = (float) $grupo->sum('hours');

                return [
                    'etiqueta' => $etiqueta((string) $clave, $grupo),
                    'horas' => $horas,
                    // Sin horas no hay porcentaje que calcular: evita la división entre cero.
                    'porcentaje' => $total > 0 ? round(($horas / $total) * 100, 1) : 0.0,
                    'renglones' => $grupo->count(),
                ];
            })
            ->sortByDesc('horas')
            ->values()
            ->all();
    }
}
