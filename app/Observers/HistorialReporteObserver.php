<?php

namespace App\Observers;

use App\Models\{DeudaCuota, Liquidacion, TipoCaja};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Registra variaciones, no reescribe cierres anteriores con el saldo actual. */
class HistorialReporteObserver
{
    public function created(Model $modelo): void
    {
        $this->registrar($modelo, true);
    }

    public function updated(Model $modelo): void
    {
        $this->registrar($modelo, false);
    }

    private function registrar(Model $modelo, bool $creacion): void
    {
        if (!\App\Support\RegistroReporteDisponible::existe()) return;
        $anterior = $creacion ? [] : $modelo->getOriginal();
        $nuevo = $modelo->getAttributes();
        $delta = $this->saldo($modelo, $nuevo) - $this->saldo($modelo, $anterior);
        if (!$creacion && $delta === 0) return;

        $tipo = $modelo instanceof TipoCaja ? 'CAJA_INICIAL' : ($modelo instanceof DeudaCuota ? 'CUOTA' : 'LIQUIDACION');
        $origen = DB::table('reporte_eventos')->where('tipo', $tipo)->where('origen_id', $modelo->id)->orderBy('id')->first();
        $persona = $tipo === 'CAJA_INICIAL' ? null : ($tipo === 'CUOTA' ? $modelo->alumno : $modelo->profesor);
        $fecha = $modelo instanceof DeudaCuota ? ($modelo->fechaHistorial ?? today()->toDateString())
            : ($modelo instanceof Liquidacion && $modelo->estado_pago === 'PAGADA' ? $modelo->pagada_fecha?->toDateString() : null);
        $desde = DB::table('reporte_cobertura')->where('id', 1)->value('desde');
        // Un pago retroactivo no demuestra que una deuda recién creada existía antes.
        $fecha = max($fecha ?? today()->toDateString(), $desde, $origen?->fecha ?? today()->toDateString());
        DB::table('reporte_eventos')->insert([
            'tipo' => $tipo, 'origen_id' => $modelo->id, 'persona_id' => $persona?->id,
            'deuda_cuota_id' => $tipo === 'CUOTA' ? $modelo->id : null,
            'liquidacion_id' => $tipo === 'LIQUIDACION' ? $modelo->id : null,
            'periodo' => $tipo === 'CAJA_INICIAL' ? null : ($tipo === 'CUOTA' ? $modelo->periodo : sprintf('%04d-%02d', $modelo->anio, $modelo->mes)),
            'deporte_id' => $origen ? $origen->deporte_id : $persona?->deporte_id,
            'nivel_id' => $origen ? $origen->nivel_id : ($tipo === 'CUOTA' ? $persona?->grupo?->nivel_id : null),
            'fecha' => $fecha, 'delta_centavos' => $delta,
            'motivo' => $creacion ? 'CREACION' : 'CAMBIO', 'registrado_en' => now(),
        ]);
    }

    private function saldo(Model $modelo, array $datos): int
    {
        if (!$datos) return 0;
        if ($modelo instanceof TipoCaja) return (int) round(($datos['saldo_inicial'] ?? 0) * 100);
        if ($modelo instanceof DeudaCuota) {
            return ($datos['estado'] ?? '') === 'CONDONADA' ? 0
                : max(0, (int) round(((float) ($datos['monto_original'] ?? 0) - (float) ($datos['monto_pagado'] ?? 0)) * 100));
        }
        return ($datos['estado'] ?? '') === 'CERRADA' && ($datos['estado_pago'] ?? 'PENDIENTE') === 'PENDIENTE'
            ? (int) round(($datos['total_calculado'] ?? 0) * 100) : 0;
    }
}
