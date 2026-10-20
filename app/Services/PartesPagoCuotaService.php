<?php

namespace App\Services;

use App\Models\Pago;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** DU-19: cada importe conserva el mes pagado y el mes efectivo de comisión. */
class PartesPagoCuotaService
{
    public function pagosParaPeriodo(string $periodo): Builder
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $periodo);
        return Pago::whereIn('estado', ['pagado', Pago::ESTADO_COMPLETADO])->conCuota()
            ->where(function ($q) use ($inicio, $periodo) {
                // Sin imputaciones se conserva la selección histórica por mes/anio.
                $q->where(fn ($q) => $q->doesntHave('deudasCuota')
                    ->where('mes', $inicio->month)->where('anio', $inicio->year))
                    ->orWhere(fn ($q) => $q->has('deudasCuota')
                        ->whereDate('fecha_pago', '<=', $inicio->endOfMonth()->toDateString())
                        ->where(fn ($q) => $q->whereDate('fecha_pago', '>=', $inicio->toDateString())
                            ->orWhereHas('deudasCuota', fn ($q) => $q->where('periodo', $periodo))));
            })->with(['alumno', 'deudasCuota']);
    }

    public function partes(Pago $pago, ?string $fechaCobro = null): array
    {
        $mesCobro = $fechaCobro ? substr($fechaCobro, 0, 7)
            : ($pago->fecha_pago?->format('Y-m') ?? sprintf('%04d-%02d', $pago->anio, $pago->mes));
        $imputaciones = $pago->deudasCuota->map(fn ($deuda) => [
            'deuda_id' => $deuda->id, 'periodo' => $deuda->periodo,
            'monto_aplicado' => $deuda->pivot->monto_aplicado,
        ])->all();
        // La anulación conserva el desglose original antes de retirar los pivotes.
        if (!$imputaciones && $pago->estado === Pago::ESTADO_ANULADO) {
            $imputaciones = $pago->detalle_anulacion['periodos'] ?? [];
        }
        if (!$imputaciones) {
            return [[
                'pago_id' => $pago->id, 'deuda_id' => null, 'periodo_cuota' => null,
                'periodo_comision' => sprintf('%04d-%02d', $pago->anio, $pago->mes),
                'centavos' => (int) round($pago->monto_cuota * 100), 'origen' => null,
            ]];
        }
        return array_map(fn ($parte) => [
            'pago_id' => $pago->id, 'deuda_id' => $parte['deuda_id'] ?? null, 'periodo_cuota' => $parte['periodo'],
            'periodo_comision' => max($mesCobro, $parte['periodo']),
            'centavos' => (int) round((float) $parte['monto_aplicado'] * 100),
            'origen' => $parte['periodo'] > $mesCobro ? 'adelantado' : ($parte['periodo'] < $mesCobro ? 'atrasado' : 'del_mes'),
        ], $imputaciones);
    }

    public function centavosParaPeriodo(Pago $pago, string $periodo): int
    {
        return array_sum(array_column(array_filter($this->partes($pago),
            fn ($parte) => $parte['periodo_comision'] === $periodo), 'centavos'));
    }
}
