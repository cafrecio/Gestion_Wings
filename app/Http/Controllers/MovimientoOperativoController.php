<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoOperativoRequest;
use App\Models\MovimientoOperativo;
use App\Services\CajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovimientoOperativoController extends Controller
{
    protected CajaService $cajaService;

    public function __construct(CajaService $cajaService)
    {
        $this->cajaService = $cajaService;
    }

    /**
     * Registrar un movimiento operativo
     *
     * Abre caja automáticamente si no existe.
     *
     * POST /api/movimientos-operativos
     *
     * Body:
     * {
     *   "usuario_operativo_id": 1,      // Requerido (temporal hasta auth)
     *   "tipo_caja_id": 1,              // Requerido
     *   "subrubro_id": 3,               // Requerido
     *   "monto": 1500.00,               // Requerido
     *   "observaciones": "Pago clase",  // Opcional
     *   "fecha": "2026-02-01"           // Opcional
     * }
     */
    public function store(StoreMovimientoOperativoRequest $request): JsonResponse
    {
        try {
            $usuarioOperativoId = auth()->id();

            $movimiento = $this->cajaService->registrarMovimientoOperativo([
                'usuario_operativo_id' => $usuarioOperativoId,
                'tipo_caja_id' => $request->validated('tipo_caja_id'),
                'subrubro_id' => $request->validated('subrubro_id'),
                'monto' => $request->validated('monto'),
                'observaciones' => $request->validated('observaciones'),
                'fecha' => $request->validated('fecha'),
            ]);

            $movimiento->load([
                'cajaOperativa',
                'tipoCaja',
                'subrubro.rubro',
                'usuario',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento registrado exitosamente.',
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
     * Listar movimientos de una caja
     *
     * GET /api/cajas/{cajaId}/movimientos
     */
    public function indexByCaja(int $cajaId): JsonResponse
    {
        try {
            $movimientos = MovimientoOperativo::where('caja_operativa_id', $cajaId)
                ->with(['tipoCaja', 'subrubro.rubro', 'usuario'])
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
}
