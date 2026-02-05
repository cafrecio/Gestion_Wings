<?php

namespace App\Services;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use Illuminate\Support\Facades\DB;

class CajaResumenService
{
    /**
     * Generar resumen completo de una caja operativa
     *
     * @param int $cajaId
     * @return array
     * @throws \Exception
     */
    public function resumen(int $cajaId): array
    {
        $caja = CajaOperativa::with([
            'usuarioOperativo',
            'usuarioAdminCierre',
            'usuarioAdminValidacion',
        ])->findOrFail($cajaId);

        $movimientos = MovimientoOperativo::where('caja_operativa_id', $cajaId)
            ->with(['tipoCaja', 'subrubro.rubro', 'usuario'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Calcular totales por tipo de caja
        $totalesPorTipoCaja = $this->calcularTotalesPorTipoCaja($movimientos);

        // Calcular totales por naturaleza (INGRESO/EGRESO)
        $totalesPorNaturaleza = $this->calcularTotalesPorNaturaleza($movimientos);

        // Calcular totales generales
        $totalesGenerales = $this->calcularTotalesGenerales($totalesPorNaturaleza);

        // Formatear listado de movimientos
        $listadoMovimientos = $this->formatearMovimientos($movimientos);

        return [
            'caja' => [
                'id' => $caja->id,
                'estado' => $caja->estado,
                'apertura_at' => $caja->apertura_at->toIso8601String(),
                'cierre_at' => $caja->cierre_at?->toIso8601String(),
                'validada_at' => $caja->validada_at?->toIso8601String(),
                'cerrada_por_admin' => $caja->cerrada_por_admin,
                'motivo_rechazo' => $caja->motivo_rechazo,
                'usuario_operativo' => [
                    'id' => $caja->usuarioOperativo->id,
                    'name' => $caja->usuarioOperativo->name,
                ],
                'usuario_admin_cierre' => $caja->usuarioAdminCierre ? [
                    'id' => $caja->usuarioAdminCierre->id,
                    'name' => $caja->usuarioAdminCierre->name,
                ] : null,
                'usuario_admin_validacion' => $caja->usuarioAdminValidacion ? [
                    'id' => $caja->usuarioAdminValidacion->id,
                    'name' => $caja->usuarioAdminValidacion->name,
                ] : null,
            ],
            'totales_por_tipo_caja' => $totalesPorTipoCaja,
            'totales_por_naturaleza' => $totalesPorNaturaleza,
            'totales_generales' => $totalesGenerales,
            'movimientos' => $listadoMovimientos,
            'cantidad_movimientos' => count($listadoMovimientos),
        ];
    }

    /**
     * Calcular totales agrupados por tipo de caja (Efectivo, Banco, MP, etc.)
     *
     * @param \Illuminate\Database\Eloquent\Collection $movimientos
     * @return array
     */
    private function calcularTotalesPorTipoCaja($movimientos): array
    {
        $totales = [];

        foreach ($movimientos as $mov) {
            $tipoCajaId = $mov->tipo_caja_id;
            $tipoCajaNombre = $mov->tipoCaja->nombre;
            $naturaleza = $mov->subrubro->rubro->tipo; // INGRESO o EGRESO

            if (!isset($totales[$tipoCajaId])) {
                $totales[$tipoCajaId] = [
                    'tipo_caja_id' => $tipoCajaId,
                    'tipo_caja_nombre' => $tipoCajaNombre,
                    'ingresos' => 0,
                    'egresos' => 0,
                    'neto' => 0,
                ];
            }

            if ($naturaleza === 'INGRESO') {
                $totales[$tipoCajaId]['ingresos'] += (float) $mov->monto;
            } else {
                $totales[$tipoCajaId]['egresos'] += (float) $mov->monto;
            }
        }

        // Calcular neto y redondear
        foreach ($totales as &$total) {
            $total['ingresos'] = round($total['ingresos'], 2);
            $total['egresos'] = round($total['egresos'], 2);
            $total['neto'] = round($total['ingresos'] - $total['egresos'], 2);
        }

        return array_values($totales);
    }

    /**
     * Calcular totales agrupados por naturaleza (INGRESO/EGRESO)
     * con desglose por rubro y subrubro
     *
     * @param \Illuminate\Database\Eloquent\Collection $movimientos
     * @return array
     */
    private function calcularTotalesPorNaturaleza($movimientos): array
    {
        $resultado = [
            'INGRESO' => [
                'total' => 0,
                'rubros' => [],
            ],
            'EGRESO' => [
                'total' => 0,
                'rubros' => [],
            ],
        ];

        foreach ($movimientos as $mov) {
            $naturaleza = $mov->subrubro->rubro->tipo;
            $rubroId = $mov->subrubro->rubro->id;
            $rubroNombre = $mov->subrubro->rubro->nombre;
            $subrubroId = $mov->subrubro->id;
            $subrubroNombre = $mov->subrubro->nombre;
            $monto = (float) $mov->monto;

            // Inicializar rubro si no existe
            if (!isset($resultado[$naturaleza]['rubros'][$rubroId])) {
                $resultado[$naturaleza]['rubros'][$rubroId] = [
                    'rubro_id' => $rubroId,
                    'rubro_nombre' => $rubroNombre,
                    'total' => 0,
                    'subrubros' => [],
                ];
            }

            // Inicializar subrubro si no existe
            if (!isset($resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId])) {
                $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId] = [
                    'subrubro_id' => $subrubroId,
                    'subrubro_nombre' => $subrubroNombre,
                    'total' => 0,
                    'cantidad' => 0,
                ];
            }

            // Acumular
            $resultado[$naturaleza]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId]['cantidad']++;
        }

        // Convertir arrays asociativos a indexados y redondear
        foreach (['INGRESO', 'EGRESO'] as $nat) {
            $resultado[$nat]['total'] = round($resultado[$nat]['total'], 2);

            foreach ($resultado[$nat]['rubros'] as &$rubro) {
                $rubro['total'] = round($rubro['total'], 2);

                foreach ($rubro['subrubros'] as &$sub) {
                    $sub['total'] = round($sub['total'], 2);
                }

                $rubro['subrubros'] = array_values($rubro['subrubros']);
            }

            $resultado[$nat]['rubros'] = array_values($resultado[$nat]['rubros']);
        }

        return $resultado;
    }

    /**
     * Calcular totales generales
     *
     * @param array $totalesPorNaturaleza
     * @return array
     */
    private function calcularTotalesGenerales(array $totalesPorNaturaleza): array
    {
        $ingresos = $totalesPorNaturaleza['INGRESO']['total'];
        $egresos = $totalesPorNaturaleza['EGRESO']['total'];

        return [
            'total_ingresos' => $ingresos,
            'total_egresos' => $egresos,
            'neto' => round($ingresos - $egresos, 2),
        ];
    }

    /**
     * Formatear listado de movimientos para respuesta
     *
     * @param \Illuminate\Database\Eloquent\Collection $movimientos
     * @return array
     */
    private function formatearMovimientos($movimientos): array
    {
        return $movimientos->map(function ($mov) {
            return [
                'id' => $mov->id,
                'fecha' => $mov->fecha?->format('Y-m-d'),
                'tipo_caja' => [
                    'id' => $mov->tipoCaja->id,
                    'nombre' => $mov->tipoCaja->nombre,
                ],
                'rubro' => [
                    'id' => $mov->subrubro->rubro->id,
                    'nombre' => $mov->subrubro->rubro->nombre,
                    'tipo' => $mov->subrubro->rubro->tipo,
                ],
                'subrubro' => [
                    'id' => $mov->subrubro->id,
                    'nombre' => $mov->subrubro->nombre,
                ],
                'monto' => (float) $mov->monto,
                'observaciones' => $mov->observaciones,
                'usuario' => [
                    'id' => $mov->usuario->id,
                    'name' => $mov->usuario->name,
                ],
                'created_at' => $mov->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Obtener resúmenes (totales_generales + cantidad_movimientos) para múltiples cajas
     * en 1-2 queries eficientes (sin loop N queries)
     *
     * @param array $cajaIds
     * @return array Map de cajaId => ['totales_generales' => [...], 'cantidad_movimientos' => int]
     */
    public function resumenesPorCajas(array $cajaIds): array
    {
        if (empty($cajaIds)) {
            return [];
        }

        // Query única: agrupa por caja_operativa_id y tipo de rubro (INGRESO/EGRESO)
        // Usa JOIN con subrubros y rubros para obtener el tipo
        $totales = DB::table('movimientos_operativos as mo')
            ->join('subrubros as s', 'mo.subrubro_id', '=', 's.id')
            ->join('rubros as r', 's.rubro_id', '=', 'r.id')
            ->whereIn('mo.caja_operativa_id', $cajaIds)
            ->select(
                'mo.caja_operativa_id',
                'r.tipo',
                DB::raw('SUM(mo.monto) as total'),
                DB::raw('COUNT(*) as cantidad')
            )
            ->groupBy('mo.caja_operativa_id', 'r.tipo')
            ->get();

        // Inicializar resultado con valores por defecto para todas las cajas
        $resultado = [];
        foreach ($cajaIds as $cajaId) {
            $resultado[$cajaId] = [
                'totales_generales' => [
                    'total_ingresos' => 0,
                    'total_egresos' => 0,
                    'neto' => 0,
                ],
                'cantidad_movimientos' => 0,
            ];
        }

        // Procesar resultados de la query
        foreach ($totales as $row) {
            $cajaId = $row->caja_operativa_id;

            if ($row->tipo === 'INGRESO') {
                $resultado[$cajaId]['totales_generales']['total_ingresos'] = round((float) $row->total, 2);
            } else {
                $resultado[$cajaId]['totales_generales']['total_egresos'] = round((float) $row->total, 2);
            }

            $resultado[$cajaId]['cantidad_movimientos'] += (int) $row->cantidad;
        }

        // Calcular neto para cada caja
        foreach ($resultado as $cajaId => &$data) {
            $data['totales_generales']['neto'] = round(
                $data['totales_generales']['total_ingresos'] - $data['totales_generales']['total_egresos'],
                2
            );
        }

        return $resultado;
    }
}
