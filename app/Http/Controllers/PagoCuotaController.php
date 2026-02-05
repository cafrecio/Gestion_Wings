<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustarDeudaRequest;
use App\Http\Requests\CondonarDeudaRequest;
use App\Http\Requests\StoreDeudaCuotaRequest;
use App\Http\Requests\StorePagoCuotaAdminRequest;
use App\Http\Requests\StorePagoCuotaOperativoRequest;
use App\Models\DeudaCuota;
use App\Services\PagoCuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoCuotaController extends Controller
{
    private PagoCuotaService $pagoCuotaService;

    public function __construct(PagoCuotaService $pagoCuotaService)
    {
        $this->pagoCuotaService = $pagoCuotaService;
    }

    /**
     * Registrar pago de cuota como OPERATIVO.
     * POST /api/cuotas/pagos
     */
    public function storeOperativo(StorePagoCuotaOperativoRequest $request): JsonResponse
    {
        try {
            // TODO: Obtener usuario autenticado
            $usuarioOperativoId = $request->input('usuario_operativo_id', 1);

            $resultado = $this->pagoCuotaService->registrarPagoCuotaOperativo([
                'alumno_id' => $request->alumno_id,
                'tipo_caja_id' => $request->tipo_caja_id,
                'usuario_operativo_id' => $usuarioOperativoId,
                'items' => $request->items,
                'fecha_pago' => $request->fecha_pago,
                'observaciones' => $request->observaciones,
            ]);

            return response()->json([
                'message' => 'Pago de cuota registrado exitosamente.',
                'pago' => $resultado['pago'],
                'movimiento_operativo' => $resultado['movimiento'],
                'deudas_actualizadas' => array_values($resultado['deudas_actualizadas']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar pago de cuota.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Registrar pago de cuota como ADMIN.
     * POST /api/admin/cuotas/pagos
     */
    public function storeAdmin(StorePagoCuotaAdminRequest $request): JsonResponse
    {
        try {
            // TODO: Obtener usuario admin autenticado
            $usuarioAdminId = $request->input('usuario_admin_id', 1);

            $resultado = $this->pagoCuotaService->registrarPagoCuotaAdmin([
                'alumno_id' => $request->alumno_id,
                'tipo_caja_id' => $request->tipo_caja_id,
                'usuario_admin_id' => $usuarioAdminId,
                'items' => $request->items,
                'fecha_pago' => $request->fecha_pago,
                'observaciones' => $request->observaciones,
            ]);

            return response()->json([
                'message' => 'Pago de cuota registrado exitosamente (admin).',
                'pago' => $resultado['pago'],
                'cashflow_movimiento' => $resultado['movimiento'],
                'deudas_actualizadas' => array_values($resultado['deudas_actualizadas']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar pago de cuota.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Condonar una deuda (solo ADMIN).
     * POST /api/admin/cuotas/deudas/{id}/condonar
     */
    public function condonar(CondonarDeudaRequest $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener usuario admin autenticado
            $adminId = $request->input('usuario_admin_id', 1);

            $deuda = $this->pagoCuotaService->condonarDeuda(
                $id,
                $request->observaciones,
                $adminId
            );

            return response()->json([
                'message' => 'Deuda condonada exitosamente.',
                'deuda' => $deuda,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al condonar deuda.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Ajustar una deuda (solo ADMIN).
     * POST /api/admin/cuotas/deudas/{id}/ajustar
     */
    public function ajustar(AjustarDeudaRequest $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener usuario admin autenticado
            $adminId = $request->input('usuario_admin_id', 1);

            $deuda = $this->pagoCuotaService->ajustarDeuda(
                $id,
                $request->nuevo_monto,
                $request->observaciones,
                $adminId
            );

            return response()->json([
                'message' => 'Deuda ajustada exitosamente.',
                'deuda' => $deuda,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al ajustar deuda.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar deudas de un alumno.
     * GET /api/alumnos/{alumnoId}/deudas
     */
    public function indexByAlumno(int $alumnoId): JsonResponse
    {
        $deudas = DeudaCuota::where('alumno_id', $alumnoId)
            ->orderBy('periodo', 'desc')
            ->get();

        return response()->json([
            'alumno_id' => $alumnoId,
            'deudas' => $deudas,
        ]);
    }

    /**
     * Ver detalle de una deuda con sus pagos relacionados.
     * GET /api/deudas/{id}
     */
    public function show(int $id): JsonResponse
    {
        $deuda = DeudaCuota::with(['alumno', 'pagos'])->findOrFail($id);

        return response()->json([
            'deuda' => $deuda,
        ]);
    }

    /**
     * Crear una deuda de cuota manualmente (solo ADMIN).
     * POST /api/admin/cuotas/deudas
     */
    public function storeDeuda(StoreDeudaCuotaRequest $request): JsonResponse
    {
        try {
            $deuda = $this->pagoCuotaService->crearDeudaSiNoExiste(
                $request->alumno_id,
                $request->periodo,
                $request->monto_original
            );

            $wasRecentlyCreated = $deuda->wasRecentlyCreated;

            return response()->json([
                'message' => $wasRecentlyCreated
                    ? 'Deuda creada exitosamente.'
                    : 'La deuda ya existía para este período.',
                'deuda' => $deuda,
                'created' => $wasRecentlyCreated,
            ], $wasRecentlyCreated ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear deuda.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
