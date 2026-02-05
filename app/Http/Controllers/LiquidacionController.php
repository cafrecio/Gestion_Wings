<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagarLiquidacionRequest;
use App\Http\Requests\StoreLiquidacionRequest;
use App\Models\Liquidacion;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiquidacionController extends Controller
{
    public function __construct(
        private LiquidacionService $liquidacionService,
        private LiquidacionPagoService $liquidacionPagoService
    ) {}

    /**
     * Generar liquidación mensual para un profesor
     *
     * POST /api/liquidaciones
     */
    public function store(StoreLiquidacionRequest $request): JsonResponse
    {
        try {
            $liquidacion = $this->liquidacionService->generarLiquidacionMensual(
                $request->profesor_id,
                $request->mes,
                $request->anio
            );

            return response()->json([
                'success' => true,
                'message' => 'Liquidación generada correctamente.',
                'data' => $liquidacion,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener una liquidación específica
     *
     * GET /api/liquidaciones/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $liquidacion = Liquidacion::with(['profesor', 'detalles'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $liquidacion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Liquidación no encontrada.',
            ], 404);
        }
    }

    /**
     * Obtener liquidaciones de un profesor
     *
     * GET /api/profesores/{profesorId}/liquidaciones
     */
    public function indexByProfesor(int $profesorId, Request $request): JsonResponse
    {
        try {
            $anio = $request->query('anio');

            $liquidaciones = $this->liquidacionService->obtenerLiquidacionesProfesor(
                $profesorId,
                $anio ? (int) $anio : null
            );

            return response()->json([
                'success' => true,
                'data' => $liquidaciones,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cerrar una liquidación
     *
     * POST /api/liquidaciones/{id}/cerrar
     */
    public function cerrar(int $id): JsonResponse
    {
        try {
            $liquidacion = $this->liquidacionService->cerrarLiquidacion($id);

            return response()->json([
                'success' => true,
                'message' => 'Liquidación cerrada correctamente.',
                'data' => $liquidacion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Recalcular una liquidación abierta
     *
     * POST /api/liquidaciones/{id}/recalcular
     */
    public function recalcular(int $id): JsonResponse
    {
        try {
            $liquidacion = $this->liquidacionService->recalcularLiquidacion($id);

            return response()->json([
                'success' => true,
                'message' => 'Liquidación recalculada correctamente.',
                'data' => $liquidacion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar una liquidación abierta
     *
     * DELETE /api/liquidaciones/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->liquidacionService->eliminarLiquidacion($id);

            return response()->json([
                'success' => true,
                'message' => 'Liquidación eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener resumen de liquidaciones de un período
     *
     * GET /api/liquidaciones/resumen/{mes}/{anio}
     */
    public function resumenPeriodo(int $mes, int $anio): JsonResponse
    {
        try {
            $resumen = $this->liquidacionService->obtenerResumenPeriodo($mes, $anio);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Previsualizar liquidación sin guardar
     *
     * GET /api/liquidaciones/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'profesor_id' => 'required|integer|exists:profesores,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:2100',
        ]);

        try {
            $preview = $this->liquidacionService->previsualizarLiquidacion(
                $request->profesor_id,
                $request->mes,
                $request->anio
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Pagar una liquidación (solo ADMIN)
     *
     * POST /api/admin/liquidaciones/{id}/pagar
     */
    public function pagar(PagarLiquidacionRequest $request, int $id): JsonResponse
    {
        try {
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Usuario no autenticado.',
                ], 401);
            }

            $resultado = $this->liquidacionPagoService->marcarComoPagada($id, [
                'fecha_pago' => $request->fecha_pago,
                'tipo_caja_id' => $request->tipo_caja_id,
                'subrubro_id' => $request->subrubro_id,
                'observaciones' => $request->observaciones,
                'admin_id' => $adminId,
            ]);

            $statusCode = $resultado['ya_pagada'] ? 200 : 201;

            return response()->json([
                'success' => true,
                'message' => $resultado['ya_pagada']
                    ? 'La liquidación ya estaba pagada.'
                    : 'Liquidación marcada como pagada.',
                'ya_pagada' => $resultado['ya_pagada'],
                'liquidacion' => [
                    'id' => $resultado['liquidacion']->id,
                    'estado' => $resultado['liquidacion']->estado,
                    'estado_pago' => $resultado['liquidacion']->estado_pago,
                    'pagada_fecha' => $resultado['liquidacion']->pagada_fecha?->toDateString(),
                    'pagada_at' => $resultado['liquidacion']->pagada_at?->toIso8601String(),
                    'total_calculado' => $resultado['liquidacion']->total_calculado,
                ],
                'cashflow_movimiento' => $resultado['cashflow_movimiento'] ? [
                    'id' => $resultado['cashflow_movimiento']->id,
                    'monto' => $resultado['cashflow_movimiento']->monto,
                    'fecha' => $resultado['cashflow_movimiento']->fecha->toDateString(),
                    'tipo_caja_id' => $resultado['cashflow_movimiento']->tipo_caja_id,
                    'subrubro_id' => $resultado['cashflow_movimiento']->subrubro_id,
                ] : null,
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
