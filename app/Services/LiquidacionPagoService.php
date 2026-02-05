<?php

namespace App\Services;

use App\Models\CashflowMovimiento;
use App\Models\Liquidacion;
use App\Models\Subrubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidacionPagoService
{
    private ?ReciboService $reciboService;

    public function __construct(?ReciboService $reciboService = null)
    {
        $this->reciboService = $reciboService;
    }

    /**
     * Marcar una liquidación como pagada y reflejar en cashflow.
     *
     * @param int $liquidacionId
     * @param array $data [fecha_pago?, tipo_caja_id, subrubro_id, observaciones?, admin_id]
     * @return array ['liquidacion' => Liquidacion, 'cashflow_movimiento' => CashflowMovimiento, 'ya_pagada' => bool]
     * @throws \Exception
     */
    public function marcarComoPagada(int $liquidacionId, array $data): array
    {
        return DB::transaction(function () use ($liquidacionId, $data) {
            $liquidacion = Liquidacion::with('profesor')->findOrFail($liquidacionId);

            // Verificar si ya está pagada (idempotencia)
            if ($liquidacion->estaPagada()) {
                // Ya pagada, verificar que existe el cashflow
                $cashflowExistente = CashflowMovimiento::where('referencia_tipo', 'LIQUIDACION')
                    ->where('referencia_id', $liquidacionId)
                    ->first();

                return [
                    'liquidacion' => $liquidacion,
                    'cashflow_movimiento' => $cashflowExistente,
                    'ya_pagada' => true,
                ];
            }

            // Validar que esté CERRADA
            if (!$liquidacion->estaCerrada()) {
                throw new \Exception("La liquidación #{$liquidacionId} debe estar CERRADA para poder pagarla. Estado actual: {$liquidacion->estado}");
            }

            // Validar subrubro
            $subrubro = Subrubro::with('rubro')->findOrFail($data['subrubro_id']);

            if ($subrubro->rubro->tipo !== 'EGRESO') {
                throw new \Exception("El subrubro seleccionado debe ser de tipo EGRESO. Tipo actual: {$subrubro->rubro->tipo}");
            }

            if ($subrubro->permitido_para !== 'ADMIN') {
                throw new \Exception("El subrubro seleccionado debe ser permitido para ADMIN. Permitido para: {$subrubro->permitido_para}");
            }

            // Parsear fecha de pago (default: hoy en TZ Argentina)
            $fechaPago = isset($data['fecha_pago']) && $data['fecha_pago']
                ? Carbon::parse($data['fecha_pago'])->toDateString()
                : Carbon::now('America/Argentina/Buenos_Aires')->toDateString();

            $adminId = $data['admin_id'];
            $tipoCajaId = $data['tipo_caja_id'];

            // Verificar idempotencia en cashflow
            $cashflowExistente = CashflowMovimiento::where('referencia_tipo', 'LIQUIDACION')
                ->where('referencia_id', $liquidacionId)
                ->first();

            if ($cashflowExistente) {
                // Ya existe asiento, solo marcar como pagada si no lo está
                $liquidacion->estado_pago = Liquidacion::ESTADO_PAGO_PAGADA;
                $liquidacion->pagada_at = Carbon::now();
                $liquidacion->pagada_por_admin_id = $adminId;
                $liquidacion->pagada_fecha = $fechaPago;
                $liquidacion->pagada_tipo_caja_id = $tipoCajaId;
                $liquidacion->pagada_subrubro_id = $data['subrubro_id'];
                $liquidacion->save();

                return [
                    'liquidacion' => $liquidacion->fresh(),
                    'cashflow_movimiento' => $cashflowExistente,
                    'ya_pagada' => true,
                ];
            }

            // Generar observaciones
            $observaciones = $data['observaciones'] ?? null;
            $profesorNombre = $liquidacion->profesor->nombre ?? "Profesor #{$liquidacion->profesor_id}";
            $obsCompleta = "Pago liquidación #{$liquidacionId} - {$profesorNombre} ({$liquidacion->mes}/{$liquidacion->anio})";
            if ($observaciones) {
                $obsCompleta .= " - {$observaciones}";
            }

            // Crear asiento en cashflow (monto negativo = EGRESO)
            $montoNegativo = -abs((float) $liquidacion->total_calculado);

            $cashflowMovimiento = CashflowMovimiento::create([
                'fecha' => $fechaPago,
                'subrubro_id' => $data['subrubro_id'],
                'tipo_caja_id' => $tipoCajaId,
                'monto' => $montoNegativo,
                'observaciones' => $obsCompleta,
                'usuario_admin_id' => $adminId,
                'referencia_tipo' => 'LIQUIDACION',
                'referencia_id' => $liquidacionId,
            ]);

            // Marcar liquidación como pagada
            $liquidacion->estado_pago = Liquidacion::ESTADO_PAGO_PAGADA;
            $liquidacion->pagada_at = Carbon::now();
            $liquidacion->pagada_por_admin_id = $adminId;
            $liquidacion->pagada_fecha = $fechaPago;
            $liquidacion->pagada_tipo_caja_id = $tipoCajaId;
            $liquidacion->pagada_subrubro_id = $data['subrubro_id'];
            $liquidacion->save();

            $resultado = [
                'liquidacion' => $liquidacion->fresh(),
                'cashflow_movimiento' => $cashflowMovimiento,
                'ya_pagada' => false,
            ];

            // Enganchar generación de PDF después del commit (no falla la transacción si el PDF falla)
            $liqId = $liquidacionId;
            $reciboService = $this->reciboService;
            DB::afterCommit(function () use ($liqId, $reciboService) {
                if ($reciboService) {
                    $reciboService->intentarGenerarReciboLiquidacion($liqId);
                }
            });

            return $resultado;
        });
    }
}
