<?php

namespace App\Services;

use App\Models\CashflowMovimiento;
use Illuminate\Support\Facades\DB;

class CashflowSaldoService
{
    /**
     * Obtener saldos actuales por tipo de caja
     *
     * Calcula ingresos, egresos y saldo para cada tipo de caja
     * usando cashflow_movimientos como fuente de verdad.
     *
     * @return array
     */
    public function obtenerSaldosPorTipoCaja(): array
    {
        // Query agrupada por tipo_caja_id y tipo de rubro (INGRESO/EGRESO)
        $totales = DB::table('cashflow_movimientos as cm')
            ->join('tipos_caja as tc', 'cm.tipo_caja_id', '=', 'tc.id')
            ->join('subrubros as s', 'cm.subrubro_id', '=', 's.id')
            ->join('rubros as r', 's.rubro_id', '=', 'r.id')
            ->select(
                'cm.tipo_caja_id',
                'tc.nombre as tipo_caja_nombre',
                'r.tipo as naturaleza',
                DB::raw('SUM(cm.monto) as total')
            )
            ->groupBy('cm.tipo_caja_id', 'tc.nombre', 'r.tipo')
            ->get();

        // Procesar resultados
        $porTipoCaja = [];

        foreach ($totales as $row) {
            $tipoCajaId = $row->tipo_caja_id;

            if (!isset($porTipoCaja[$tipoCajaId])) {
                $porTipoCaja[$tipoCajaId] = [
                    'tipo_caja_id' => $tipoCajaId,
                    'tipo_caja_nombre' => $row->tipo_caja_nombre,
                    'ingresos' => 0,
                    'egresos' => 0,
                    'saldo' => 0,
                ];
            }

            if ($row->naturaleza === 'INGRESO') {
                $porTipoCaja[$tipoCajaId]['ingresos'] = round((float) $row->total, 2);
            } else {
                $porTipoCaja[$tipoCajaId]['egresos'] = round((float) $row->total, 2);
            }
        }

        // Calcular saldos y totales generales
        $totalIngresos = 0;
        $totalEgresos = 0;

        foreach ($porTipoCaja as &$item) {
            $item['saldo'] = round($item['ingresos'] - $item['egresos'], 2);
            $totalIngresos += $item['ingresos'];
            $totalEgresos += $item['egresos'];
        }

        return [
            'por_tipo_caja' => array_values($porTipoCaja),
            'totales' => [
                'ingresos' => round($totalIngresos, 2),
                'egresos' => round($totalEgresos, 2),
                'saldo' => round($totalIngresos - $totalEgresos, 2),
            ],
        ];
    }
}
