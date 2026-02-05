<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashflowMovimientoRequest;
use App\Models\CashflowMovimiento;
use App\Services\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashflowMovimientoController extends Controller
{
    protected CashflowService $cashflowService;

    public function __construct(CashflowService $cashflowService)
    {
        $this->cashflowService = $cashflowService;
    }

    /**
     * Registrar un movimiento de cashflow (solo ADMIN)
     *
     * POST /api/cashflow-movimientos
     *
     * Body:
     * {
     *   "subrubro_id": 5,               // Requerido
     *   "tipo_caja_id": 1,              // Requerido
     *   "monto": 50000.00,              // Requerido
     *   "fecha": "2026-02-01",          // Opcional
     *   "observaciones": "Pago sueldo", // Opcional
     *   "referencia_tipo": "LIQUIDACION", // Opcional
     *   "referencia_id": 12             // Opcional
     * }
     */
    public function store(StoreCashflowMovimientoRequest $request): JsonResponse
    {
        try {
            $adminId = auth()->id();

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $movimiento = $this->cashflowService->registrarMovimientoAdmin([
                'usuario_admin_id' => $adminId,
                'subrubro_id' => $request->validated('subrubro_id'),
                'tipo_caja_id' => $request->validated('tipo_caja_id'),
                'monto' => $request->validated('monto'),
                'fecha' => $request->validated('fecha'),
                'observaciones' => $request->validated('observaciones'),
                'referencia_tipo' => $request->validated('referencia_tipo'),
                'referencia_id' => $request->validated('referencia_id'),
            ]);

            $movimiento->load(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin']);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de cashflow registrado exitosamente.',
                'data' => $movimiento,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el movimiento.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar movimientos de cashflow
     *
     * GET /api/cashflow-movimientos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin']);

            // Filtro por fecha desde
            if ($request->has('fecha_desde')) {
                $query->where('fecha', '>=', $request->input('fecha_desde'));
            }

            // Filtro por fecha hasta
            if ($request->has('fecha_hasta')) {
                $query->where('fecha', '<=', $request->input('fecha_hasta'));
            }

            // Filtro por subrubro
            if ($request->has('subrubro_id')) {
                $query->where('subrubro_id', $request->input('subrubro_id'));
            }

            // Filtro por referencia
            if ($request->has('referencia_tipo')) {
                $query->where('referencia_tipo', $request->input('referencia_tipo'));
            }

            $movimientos = $query->orderBy('fecha', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $movimientos,
                'total' => $movimientos->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener un movimiento específico
     *
     * GET /api/cashflow-movimientos/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $movimiento = CashflowMovimiento::with(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $movimiento,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
