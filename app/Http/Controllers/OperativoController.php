<?php

namespace App\Http\Controllers;

use App\Services\OperativoEstadoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperativoController extends Controller
{
    private const TZ_ARGENTINA = 'America/Argentina/Buenos_Aires';

    protected OperativoEstadoService $estadoService;

    public function __construct(OperativoEstadoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    /**
     * Estado operativo del día para el usuario autenticado
     *
     * GET /api/operativo/estado-hoy
     *
     * Devuelve:
     * - caja_actual: si tiene caja ABIERTA hoy
     * - ultima_caja_hoy: última caja del día (si no hay abierta)
     * - bloqueo: si tiene caja vieja abierta que impide operar
     */
    public function estadoHoy(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $hoyAr = Carbon::now(self::TZ_ARGENTINA);
            $estado = $this->estadoService->obtenerEstadoHoy($userId, $hoyAr);

            return response()->json([
                'success' => true,
                'data' => $estado,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado operativo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
