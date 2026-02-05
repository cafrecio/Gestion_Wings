<?php

namespace App\Http\Controllers;

use App\Services\CashflowIntegracionCajaService;
use App\Services\CashflowSaldoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashflowSaldoController extends Controller
{
    protected CashflowSaldoService $saldoService;
    protected CashflowIntegracionCajaService $integracionService;

    public function __construct(
        CashflowSaldoService $saldoService,
        CashflowIntegracionCajaService $integracionService
    ) {
        $this->saldoService = $saldoService;
        $this->integracionService = $integracionService;
    }

    /**
     * Obtener saldos actuales por tipo de caja (solo ADMIN)
     *
     * GET /api/admin/cashflow/saldos
     *
     * Retorna:
     * - por_tipo_caja: array con ingresos, egresos y saldo por cada tipo de caja
     * - totales: ingresos, egresos y saldo total
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // TODO: Validar que sea ADMIN cuando exista RBAC formal
            if (!auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $saldos = $this->saldoService->obtenerSaldosPorTipoCaja();

            return response()->json([
                'success' => true,
                'data' => $saldos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los saldos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener saldo acumulado hasta una fecha (solo ADMIN)
     *
     * GET /api/admin/cashflow/saldo?fecha=YYYY-MM-DD
     *
     * Retorna:
     * - por_tipo_caja: saldo acumulado por cada tipo de caja
     * - totales: saldo total acumulado
     */
    public function saldoAFecha(Request $request): JsonResponse
    {
        try {
            $fecha = $request->input('fecha');

            if (!$fecha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere el parámetro fecha (YYYY-MM-DD).',
                ], 422);
            }

            // Validar formato de fecha
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formato de fecha inválido. Use YYYY-MM-DD.',
                ], 422);
            }

            $saldos = $this->integracionService->saldoAcumuladoHastaFecha($fecha);

            return response()->json([
                'success' => true,
                'data' => $saldos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el saldo.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
