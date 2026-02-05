<?php

namespace App\Http\Controllers;

use App\Services\CierreDiaResumenService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CierreDiaController extends Controller
{
    private const TZ_ARGENTINA = 'America/Argentina/Buenos_Aires';

    protected CierreDiaResumenService $cierreDiaService;

    public function __construct(CierreDiaResumenService $cierreDiaService)
    {
        $this->cierreDiaService = $cierreDiaService;
    }

    /**
     * Cierre consolidado del día para el usuario autenticado (OPERATIVO)
     *
     * GET /api/cierres-dia?fecha=YYYY-MM-DD (opcional, default hoy)
     *
     * Consolida todas las cajas del día del usuario autenticado en un solo documento.
     */
    public function operativo(Request $request): JsonResponse
    {
        try {
            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Fecha opcional, default hoy
            $fechaAr = $request->has('fecha')
                ? Carbon::parse($request->input('fecha'), self::TZ_ARGENTINA)
                : Carbon::now(self::TZ_ARGENTINA);

            $resumen = $this->cierreDiaService->resumenOperativoPorFecha($usuarioId, $fechaAr);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cierre del día.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cierre consolidado del día para ADMIN
     *
     * GET /api/admin/cierres-dia?fecha=YYYY-MM-DD&usuario_operativo_id=X
     *
     * - Si viene usuario_operativo_id: devuelve cierre de ese operativo
     * - Si NO viene: devuelve consolidado global del día con resumen por operativo
     */
    public function admin(Request $request): JsonResponse
    {
        try {
            // TODO: Validar que sea ADMIN cuando exista RBAC formal
            // Por ahora check simple si hay usuario autenticado
            if (!auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Fecha obligatoria para admin
            if (!$request->has('fecha')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere el parámetro fecha (YYYY-MM-DD).',
                ], 422);
            }

            $fechaAr = Carbon::parse($request->input('fecha'), self::TZ_ARGENTINA);
            $usuarioOperativoId = $request->input('usuario_operativo_id');

            $resumen = $this->cierreDiaService->resumenGlobalPorFecha(
                $fechaAr,
                $usuarioOperativoId ? (int) $usuarioOperativoId : null
            );

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cierre del día.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
