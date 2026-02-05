<?php

namespace App\Services;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashflowIntegracionCajaService
{
    /**
     * Reflejar los movimientos de una caja validada en el cashflow.
     *
     * Genera asientos en cashflow_movimientos equivalentes a cada movimiento_operativo.
     * Es idempotente: si ya existen asientos para esta caja, no hace nada.
     *
     * @param int $cajaId
     * @param int $adminId
     * @throws \Exception
     */
    public function reflejarCajaEnCashflow(int $cajaId, int $adminId): void
    {
        $caja = CajaOperativa::with(['movimientos.subrubro.rubro'])->findOrFail($cajaId);

        // Solo cajas VALIDADAS pueden reflejarse en cashflow
        if ($caja->estado !== 'VALIDADA') {
            throw new \Exception("La caja #{$cajaId} no está VALIDADA. Estado actual: {$caja->estado}");
        }

        // Verificar idempotencia: si ya existen asientos para esta caja, no hacer nada
        $existenAsientos = CashflowMovimiento::where('referencia_tipo', 'CAJA_OPERATIVA')
            ->where('referencia_id', $cajaId)
            ->exists();

        if ($existenAsientos) {
            // Ya fue procesada, no duplicar
            return;
        }

        // Si no hay movimientos, no hay nada que reflejar
        if ($caja->movimientos->isEmpty()) {
            return;
        }

        // Crear asientos en cashflow por cada movimiento operativo
        foreach ($caja->movimientos as $movimiento) {
            $subrubro = $movimiento->subrubro;
            $rubro = $subrubro->rubro;

            // Determinar signo según tipo de rubro
            $esIngreso = $rubro->tipo === 'INGRESO';
            $montoConSigno = $esIngreso
                ? abs($movimiento->monto)
                : -abs($movimiento->monto);

            // Fecha del movimiento (o apertura de caja si no tiene)
            // Convertir a fecha AR
            $fechaMovimiento = $movimiento->fecha
                ?? Carbon::parse($caja->apertura_at)
                    ->timezone('America/Argentina/Buenos_Aires')
                    ->toDateString();

            CashflowMovimiento::create([
                'fecha' => $fechaMovimiento,
                'subrubro_id' => $movimiento->subrubro_id,
                'tipo_caja_id' => $movimiento->tipo_caja_id,
                'monto' => $montoConSigno,
                'observaciones' => $movimiento->observaciones,
                'usuario_admin_id' => $adminId,
                'referencia_tipo' => 'CAJA_OPERATIVA',
                'referencia_id' => $cajaId,
            ]);
        }
    }

    /**
     * Obtener saldo acumulado del cashflow hasta una fecha (inclusive).
     *
     * @param string $fecha Formato YYYY-MM-DD (interpretado en TZ Argentina)
     * @return array ['por_tipo_caja' => [...], 'totales' => [...]]
     */
    public function saldoAcumuladoHastaFecha(string $fecha): array
    {
        // Interpretar fecha en TZ Argentina
        $fechaAr = Carbon::parse($fecha, 'America/Argentina/Buenos_Aires')->toDateString();

        // Obtener saldos agrupados por tipo de caja
        $saldosPorTipo = CashflowMovimiento::select('tipo_caja_id', DB::raw('SUM(monto) as saldo'))
            ->where('fecha', '<=', $fechaAr)
            ->groupBy('tipo_caja_id')
            ->with('tipoCaja')
            ->get();

        $porTipoCaja = [];
        $totalGeneral = 0;

        foreach ($saldosPorTipo as $item) {
            $saldo = (float) $item->saldo;
            $porTipoCaja[] = [
                'tipo_caja_id' => $item->tipo_caja_id,
                'tipo_caja_nombre' => $item->tipoCaja->nombre ?? 'Desconocido',
                'saldo' => round($saldo, 2),
            ];
            $totalGeneral += $saldo;
        }

        return [
            'fecha_hasta' => $fechaAr,
            'por_tipo_caja' => $porTipoCaja,
            'totales' => [
                'saldo_total' => round($totalGeneral, 2),
            ],
        ];
    }
}
