<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Models\Alumno;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    protected PagoService $pagoService;

    public function __construct(PagoService $pagoService)
    {
        $this->pagoService = $pagoService;
    }

    /**
     * Registrar un nuevo pago
     *
     * POST /api/pagos
     *
     * Body:
     * {
     *   "alumno_id": 1,
     *   "mes": 1,
     *   "anio": 2026,
     *   "forma_pago_id": 1,
     *   "fecha_pago": "2026-01-25",        // Requerido
     *   "observaciones": "Pago inicial",   // Opcional
     *   "porcentaje_manual": 70.00,        // Opcional
     *   "regla_primer_pago_id": 2          // Opcional
     * }
     */
    public function store(StorePagoRequest $request): JsonResponse
    {
        try {
            $pago = $this->pagoService->registrarPago(
                alumnoId: $request->validated('alumno_id'),
                mes: $request->validated('mes'),
                anio: $request->validated('anio'),
                formaPagoId: $request->validated('forma_pago_id'),
                fechaPago: $request->validated('fecha_pago'),
                observaciones: $request->validated('observaciones'),
                porcentajeManual: $request->validated('porcentaje_manual'),
                reglaPrimerPagoId: $request->validated('regla_primer_pago_id'),
            );

            // Cargar relaciones para la respuesta
            $pago->load(['alumno', 'plan.grupo', 'formaPago', 'reglaPrimerPago']);

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente.',
                'data' => $pago,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener pagos de un alumno
     *
     * GET /api/alumnos/{alumnoId}/pagos
     */
    public function index(int $alumnoId): JsonResponse
    {
        try {
            $alumno = Alumno::findOrFail($alumnoId);

            $pagos = $alumno->pagos()
                ->with(['plan.grupo', 'formaPago', 'reglaPrimerPago'])
                ->orderBy('anio', 'desc')
                ->orderBy('mes', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pagos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtener el próximo pago a realizar para un alumno
     *
     * GET /api/alumnos/{alumnoId}/proximo-pago
     */
    public function proximoPago(int $alumnoId): JsonResponse
    {
        try {
            $proximoPago = $this->pagoService->obtenerProximoPago($alumnoId);

            if (!$proximoPago) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede calcular el próximo pago. El alumno no tiene plan activo.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $proximoPago,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el próximo pago.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtener reglas de primer pago disponibles para un día específico
     *
     * GET /api/reglas-primer-pago/dia/{dia}
     */
    public function reglasDisponibles(int $dia): JsonResponse
    {
        try {
            if ($dia < 1 || $dia > 31) {
                return response()->json([
                    'success' => false,
                    'message' => 'El día debe estar entre 1 y 31.',
                ], 422);
            }

            $reglas = $this->pagoService->obtenerReglasDisponibles($dia);

            return response()->json([
                'success' => true,
                'data' => $reglas,
                'total' => $reglas->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reglas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar el plan de un alumno
     *
     * POST /api/alumnos/{alumnoId}/cambiar-plan
     *
     * Body:
     * {
     *   "plan_id": 3
     * }
     */
    public function cambiarPlan(Request $request, int $alumnoId): JsonResponse
    {
        try {
            $request->validate([
                'plan_id' => 'required|exists:grupo_planes,id',
            ]);

            $nuevoPlan = $this->pagoService->cambiarPlan(
                alumnoId: $alumnoId,
                nuevoPlanId: $request->input('plan_id')
            );

            $nuevoPlan->load(['alumno', 'plan.grupo']);

            return response()->json([
                'success' => true,
                'message' => 'Plan cambiado exitosamente. Los pagos futuros usarán el nuevo plan.',
                'data' => $nuevoPlan,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el plan.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar si un alumno puede registrar un pago
     *
     * GET /api/alumnos/{alumnoId}/puede-pagar
     */
    public function verificarPuedePagar(int $alumnoId): JsonResponse
    {
        try {
            $resultado = $this->pagoService->verificarPuedePagar($alumnoId);

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar si el alumno puede pagar.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
