<?php

namespace App\Services;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CierreDiaResumenService
{
    private const TZ_ARGENTINA = 'America/Argentina/Buenos_Aires';

    /**
     * Resumen consolidado del día para un operativo específico
     *
     * @param int $usuarioOperativoId
     * @param Carbon $fechaAr Fecha en timezone Argentina
     * @return array
     */
    public function resumenOperativoPorFecha(int $usuarioOperativoId, Carbon $fechaAr): array
    {
        $usuario = User::findOrFail($usuarioOperativoId);

        // Convertir fecha a rango UTC
        [$inicioUtc, $finUtc] = $this->rangoDelDiaEnUtc($fechaAr);

        // Obtener cajas del usuario en ese día
        $cajas = CajaOperativa::where('usuario_operativo_id', $usuarioOperativoId)
            ->whereBetween('apertura_at', [$inicioUtc, $finUtc])
            ->orderBy('apertura_at', 'asc')
            ->get();

        if ($cajas->isEmpty()) {
            return $this->resumenVacio($fechaAr, $usuario);
        }

        $cajaIds = $cajas->pluck('id')->toArray();

        // Obtener todos los movimientos de esas cajas
        $movimientos = MovimientoOperativo::whereIn('caja_operativa_id', $cajaIds)
            ->with(['tipoCaja', 'subrubro.rubro', 'usuario'])
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'fecha' => $fechaAr->format('Y-m-d'),
            'usuario_operativo' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
            ],
            'cajas_incluidas' => $this->formatearCajasIncluidas($cajas),
            'totales_por_tipo_caja' => $this->calcularTotalesPorTipoCaja($movimientos),
            'totales_por_naturaleza' => $this->calcularTotalesPorNaturaleza($movimientos),
            'totales_generales' => $this->calcularTotalesGenerales($movimientos),
            'cantidad_movimientos' => $movimientos->count(),
            'movimientos' => $this->formatearMovimientos($movimientos),
        ];
    }

    /**
     * Resumen global del día (todos los operativos o uno específico)
     *
     * @param Carbon $fechaAr Fecha en timezone Argentina
     * @param int|null $usuarioOperativoId Si se especifica, devuelve solo ese operativo
     * @return array
     */
    public function resumenGlobalPorFecha(Carbon $fechaAr, ?int $usuarioOperativoId = null): array
    {
        // Si viene usuario específico, usar el método de operativo
        if ($usuarioOperativoId !== null) {
            return $this->resumenOperativoPorFecha($usuarioOperativoId, $fechaAr);
        }

        // Convertir fecha a rango UTC
        [$inicioUtc, $finUtc] = $this->rangoDelDiaEnUtc($fechaAr);

        // Obtener todas las cajas del día (de todos los operativos)
        $cajas = CajaOperativa::with('usuarioOperativo')
            ->whereBetween('apertura_at', [$inicioUtc, $finUtc])
            ->orderBy('usuario_operativo_id')
            ->orderBy('apertura_at', 'asc')
            ->get();

        if ($cajas->isEmpty()) {
            return [
                'fecha' => $fechaAr->format('Y-m-d'),
                'consolidado' => [
                    'totales_por_tipo_caja' => [],
                    'totales_por_naturaleza' => [
                        'INGRESO' => ['total' => 0, 'rubros' => []],
                        'EGRESO' => ['total' => 0, 'rubros' => []],
                    ],
                    'totales_generales' => [
                        'total_ingresos' => 0,
                        'total_egresos' => 0,
                        'neto' => 0,
                    ],
                    'cantidad_movimientos' => 0,
                    'cantidad_cajas' => 0,
                ],
                'cierres_por_operativo' => [],
            ];
        }

        $cajaIds = $cajas->pluck('id')->toArray();

        // Obtener todos los movimientos del día (de todas las cajas)
        $movimientos = MovimientoOperativo::whereIn('caja_operativa_id', $cajaIds)
            ->with(['tipoCaja', 'subrubro.rubro', 'usuario', 'cajaOperativa'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Agrupar cajas por usuario
        $cajasPorUsuario = $cajas->groupBy('usuario_operativo_id');

        // Calcular resumen por operativo
        $cierresPorOperativo = [];
        foreach ($cajasPorUsuario as $usuarioId => $cajasDelUsuario) {
            $cajasDelUsuarioIds = $cajasDelUsuario->pluck('id')->toArray();
            $movimientosDelUsuario = $movimientos->whereIn('caja_operativa_id', $cajasDelUsuarioIds);

            $cierresPorOperativo[] = [
                'usuario_operativo' => [
                    'id' => $cajasDelUsuario->first()->usuarioOperativo->id,
                    'name' => $cajasDelUsuario->first()->usuarioOperativo->name,
                ],
                'cantidad_cajas' => $cajasDelUsuario->count(),
                'totales_generales' => $this->calcularTotalesGenerales($movimientosDelUsuario),
                'cantidad_movimientos' => $movimientosDelUsuario->count(),
            ];
        }

        return [
            'fecha' => $fechaAr->format('Y-m-d'),
            'consolidado' => [
                'totales_por_tipo_caja' => $this->calcularTotalesPorTipoCaja($movimientos),
                'totales_por_naturaleza' => $this->calcularTotalesPorNaturaleza($movimientos),
                'totales_generales' => $this->calcularTotalesGenerales($movimientos),
                'cantidad_movimientos' => $movimientos->count(),
                'cantidad_cajas' => $cajas->count(),
            ],
            'cierres_por_operativo' => $cierresPorOperativo,
        ];
    }

    /**
     * Convertir fecha Argentina a rango UTC para whereBetween
     */
    private function rangoDelDiaEnUtc(Carbon $fechaAr): array
    {
        $inicioUtc = $fechaAr->copy()->setTimezone(self::TZ_ARGENTINA)->startOfDay()->utc();
        $finUtc = $fechaAr->copy()->setTimezone(self::TZ_ARGENTINA)->endOfDay()->utc();

        return [$inicioUtc, $finUtc];
    }

    /**
     * Generar resumen vacío
     */
    private function resumenVacio(Carbon $fechaAr, User $usuario): array
    {
        return [
            'fecha' => $fechaAr->format('Y-m-d'),
            'usuario_operativo' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
            ],
            'cajas_incluidas' => [],
            'totales_por_tipo_caja' => [],
            'totales_por_naturaleza' => [
                'INGRESO' => ['total' => 0, 'rubros' => []],
                'EGRESO' => ['total' => 0, 'rubros' => []],
            ],
            'totales_generales' => [
                'total_ingresos' => 0,
                'total_egresos' => 0,
                'neto' => 0,
            ],
            'cantidad_movimientos' => 0,
            'movimientos' => [],
        ];
    }

    /**
     * Formatear cajas incluidas
     */
    private function formatearCajasIncluidas($cajas): array
    {
        return $cajas->map(function ($caja) {
            return [
                'id' => $caja->id,
                'apertura_at' => $caja->apertura_at->toIso8601String(),
                'cierre_at' => $caja->cierre_at?->toIso8601String(),
                'estado' => $caja->estado,
            ];
        })->toArray();
    }

    /**
     * Calcular totales agrupados por tipo de caja
     */
    private function calcularTotalesPorTipoCaja($movimientos): array
    {
        $totales = [];

        foreach ($movimientos as $mov) {
            $tipoCajaId = $mov->tipo_caja_id;
            $tipoCajaNombre = $mov->tipoCaja->nombre;
            $naturaleza = $mov->subrubro->rubro->tipo;

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

        foreach ($totales as &$total) {
            $total['ingresos'] = round($total['ingresos'], 2);
            $total['egresos'] = round($total['egresos'], 2);
            $total['neto'] = round($total['ingresos'] - $total['egresos'], 2);
        }

        return array_values($totales);
    }

    /**
     * Calcular totales agrupados por naturaleza (INGRESO/EGRESO) con desglose
     */
    private function calcularTotalesPorNaturaleza($movimientos): array
    {
        $resultado = [
            'INGRESO' => ['total' => 0, 'rubros' => []],
            'EGRESO' => ['total' => 0, 'rubros' => []],
        ];

        foreach ($movimientos as $mov) {
            $naturaleza = $mov->subrubro->rubro->tipo;
            $rubroId = $mov->subrubro->rubro->id;
            $rubroNombre = $mov->subrubro->rubro->nombre;
            $subrubroId = $mov->subrubro->id;
            $subrubroNombre = $mov->subrubro->nombre;
            $monto = (float) $mov->monto;

            if (!isset($resultado[$naturaleza]['rubros'][$rubroId])) {
                $resultado[$naturaleza]['rubros'][$rubroId] = [
                    'rubro_id' => $rubroId,
                    'rubro_nombre' => $rubroNombre,
                    'total' => 0,
                    'subrubros' => [],
                ];
            }

            if (!isset($resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId])) {
                $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId] = [
                    'subrubro_id' => $subrubroId,
                    'subrubro_nombre' => $subrubroNombre,
                    'total' => 0,
                    'cantidad' => 0,
                ];
            }

            $resultado[$naturaleza]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId]['total'] += $monto;
            $resultado[$naturaleza]['rubros'][$rubroId]['subrubros'][$subrubroId]['cantidad']++;
        }

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
     */
    private function calcularTotalesGenerales($movimientos): array
    {
        $ingresos = 0;
        $egresos = 0;

        foreach ($movimientos as $mov) {
            $naturaleza = $mov->subrubro->rubro->tipo;
            $monto = (float) $mov->monto;

            if ($naturaleza === 'INGRESO') {
                $ingresos += $monto;
            } else {
                $egresos += $monto;
            }
        }

        return [
            'total_ingresos' => round($ingresos, 2),
            'total_egresos' => round($egresos, 2),
            'neto' => round($ingresos - $egresos, 2),
        ];
    }

    /**
     * Formatear listado de movimientos
     */
    private function formatearMovimientos($movimientos): array
    {
        return $movimientos->map(function ($mov) {
            return [
                'id' => $mov->id,
                'caja_operativa_id' => $mov->caja_operativa_id,
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
}
