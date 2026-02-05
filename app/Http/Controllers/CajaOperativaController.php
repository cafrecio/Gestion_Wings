<?php

namespace App\Http\Controllers;

use App\Http\Requests\CerrarCajaRequest;
use App\Http\Requests\RechazarCajaRequest;
use App\Models\CajaOperativa;
use App\Services\CajaResumenService;
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaOperativaController extends Controller
{
    protected CajaService $cajaService;
    protected CajaResumenService $cajaResumenService;

    public function __construct(CajaService $cajaService, CajaResumenService $cajaResumenService)
    {
        $this->cajaService = $cajaService;
        $this->cajaResumenService = $cajaResumenService;
    }

    /**
     * Listar cajas operativas
     *
     * GET /api/cajas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CajaOperativa::with(['usuarioOperativo', 'movimientos']);

            // Filtro por estado
            if ($request->has('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            // Filtro por usuario
            if ($request->has('usuario_operativo_id')) {
                $query->where('usuario_operativo_id', $request->input('usuario_operativo_id'));
            }

            $cajas = $query->orderBy('apertura_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $cajas,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cajas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener una caja específica
     *
     * GET /api/cajas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $caja = CajaOperativa::with([
                'usuarioOperativo',
                'usuarioAdminCierre',
                'usuarioAdminValidacion',
                'movimientos.tipoCaja',
                'movimientos.subrubro.rubro',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtener caja abierta del usuario actual
     *
     * GET /api/cajas/abierta
     */
    public function cajaAbierta(Request $request): JsonResponse
    {
        try {
            // TODO: Obtener usuario desde auth cuando esté implementado
            $usuarioId = $request->input('usuario_id');

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere usuario_id.',
                ], 422);
            }

            $caja = $this->cajaService->obtenerCajaAbierta($usuarioId);

            if (!$caja) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                    'message' => 'No hay caja abierta.',
                ]);
            }

            $caja->load(['movimientos.tipoCaja', 'movimientos.subrubro.rubro']);

            return response()->json([
                'success' => true,
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la caja abierta.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cerrar caja (operativo cierra su propia caja)
     *
     * POST /api/cajas/{id}/cerrar
     */
    public function cerrar(CerrarCajaRequest $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener usuario desde auth cuando esté implementado
            $usuarioId = $request->input('usuario_id');

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere usuario_id.',
                ], 422);
            }

            $caja = $this->cajaService->cerrarCajaOperativa($id, $usuarioId, false);

            $caja->load(['usuarioOperativo', 'movimientos']);

            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada exitosamente.',
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la caja.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cerrar caja como admin (cierre delegado)
     *
     * POST /api/cajas/{id}/cerrar-admin
     */
    public function cerrarComoAdmin(Request $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener admin desde auth y validar rol
            $adminId = $request->input('admin_id');

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere admin_id.',
                ], 422);
            }

            $caja = $this->cajaService->cerrarCajaOperativa($id, $adminId, true);

            $caja->load(['usuarioOperativo', 'usuarioAdminCierre', 'movimientos']);

            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada por administrador exitosamente.',
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar la caja.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validar caja (solo admin)
     *
     * POST /api/cajas/{id}/validar
     */
    public function validar(Request $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener admin desde auth y validar rol
            $adminId = $request->input('admin_id');

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere admin_id.',
                ], 422);
            }

            $caja = $this->cajaService->validarCaja($id, $adminId);

            $caja->load(['usuarioOperativo', 'usuarioAdminValidacion', 'movimientos']);

            return response()->json([
                'success' => true,
                'message' => 'Caja validada exitosamente.',
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar la caja.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rechazar caja (solo admin)
     *
     * POST /api/cajas/{id}/rechazar
     */
    public function rechazar(RechazarCajaRequest $request, int $id): JsonResponse
    {
        try {
            // TODO: Obtener admin desde auth y validar rol
            $adminId = $request->input('admin_id');

            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere admin_id.',
                ], 422);
            }

            $caja = $this->cajaService->rechazarCaja(
                $id,
                $adminId,
                $request->validated('motivo')
            );

            $caja->load(['usuarioOperativo', 'usuarioAdminValidacion', 'movimientos']);

            return response()->json([
                'success' => true,
                'message' => 'Caja rechazada.',
                'data' => $caja,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar la caja.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Listar MIS cajas por fecha (solo OPERATIVO)
     *
     * GET /api/cajas/mias?fecha=YYYY-MM-DD
     *
     * Devuelve todas las cajas del usuario logueado cuya apertura_at caiga en esa fecha.
     * Incluye totales_generales y cantidad_movimientos (query eficiente).
     * Siempre filtra por el usuario autenticado (ignora cualquier usuario_id en query params).
     */
    public function misCajas(Request $request): JsonResponse
    {
        try {
            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Fecha obligatoria
            $fecha = $request->input('fecha');
            if (!$fecha) {
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere el parámetro fecha (YYYY-MM-DD).',
                ], 422);
            }

            // Timezone Argentina para filtrar por "día"
            $tz = 'America/Argentina/Buenos_Aires';
            $inicioDelDia = Carbon::parse($fecha, $tz)->startOfDay()->utc();
            $finDelDia = Carbon::parse($fecha, $tz)->endOfDay()->utc();

            // Obtener cajas del usuario en esa fecha (día en TZ Argentina)
            $cajas = CajaOperativa::where('usuario_operativo_id', $usuarioId)
                ->whereBetween('apertura_at', [$inicioDelDia, $finDelDia])
                ->orderBy('apertura_at', 'asc')
                ->get();

            if ($cajas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            // Obtener resúmenes eficientes (1 query)
            $cajaIds = $cajas->pluck('id')->toArray();
            $resumenes = $this->cajaResumenService->resumenesPorCajas($cajaIds);

            // Formatear respuesta
            $data = $cajas->map(function ($caja) use ($resumenes) {
                $resumen = $resumenes[$caja->id] ?? [
                    'totales_generales' => ['total_ingresos' => 0, 'total_egresos' => 0, 'neto' => 0],
                    'cantidad_movimientos' => 0,
                ];

                return [
                    'id' => $caja->id,
                    'apertura_at' => $caja->apertura_at->toIso8601String(),
                    'cierre_at' => $caja->cierre_at?->toIso8601String(),
                    'estado' => $caja->estado,
                    'cerrada_por_admin' => $caja->cerrada_por_admin,
                    'validada_at' => $caja->validada_at?->toIso8601String(),
                    'totales_generales' => $resumen['totales_generales'],
                    'cantidad_movimientos' => $resumen['cantidad_movimientos'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cajas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener cajas pendientes de acción (solo ADMIN)
     *
     * GET /api/cajas/pendientes?fecha=YYYY-MM-DD (fecha opcional)
     *
     * Devuelve cajas con estado:
     * - CERRADA o RECHAZADA (pendientes de revisión)
     * - ABIERTA con apertura_at < hoy (caja vieja abierta, alerta)
     *
     * Incluye totales_generales, cantidad_movimientos y usuario_operativo.
     */
    public function pendientes(Request $request): JsonResponse
    {
        try {
            // Timezone Argentina para filtrar por "día"
            $tz = 'America/Argentina/Buenos_Aires';
            $hoyInicio = Carbon::now($tz)->startOfDay()->utc();

            $query = CajaOperativa::with('usuarioOperativo')
                ->where(function ($q) use ($hoyInicio) {
                    // Cajas CERRADAS o RECHAZADAS (pendientes de revisión)
                    $q->whereIn('estado', ['CERRADA', 'RECHAZADA'])
                        // O cajas ABIERTAS de días anteriores (alerta de caja vieja)
                        ->orWhere(function ($q2) use ($hoyInicio) {
                            $q2->where('estado', 'ABIERTA')
                                ->where('apertura_at', '<', $hoyInicio);
                        });
                });

            // Filtro opcional por fecha (día en TZ Argentina)
            if ($request->has('fecha')) {
                $inicioDelDia = Carbon::parse($request->input('fecha'), $tz)->startOfDay()->utc();
                $finDelDia = Carbon::parse($request->input('fecha'), $tz)->endOfDay()->utc();
                $query->whereBetween('apertura_at', [$inicioDelDia, $finDelDia]);
            }

            $cajas = $query->orderBy('apertura_at', 'desc')->get();

            if ($cajas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'total' => 0,
                ]);
            }

            // Obtener resúmenes eficientes (1 query)
            $cajaIds = $cajas->pluck('id')->toArray();
            $resumenes = $this->cajaResumenService->resumenesPorCajas($cajaIds);

            // Formatear respuesta
            $data = $cajas->map(function ($caja) use ($resumenes, $hoyInicio) {
                $resumen = $resumenes[$caja->id] ?? [
                    'totales_generales' => ['total_ingresos' => 0, 'total_egresos' => 0, 'neto' => 0],
                    'cantidad_movimientos' => 0,
                ];

                // Flag para indicar si es caja vieja abierta (alerta)
                $esCajaViejaAbierta = $caja->estado === 'ABIERTA'
                    && $caja->apertura_at < $hoyInicio;

                return [
                    'id' => $caja->id,
                    'apertura_at' => $caja->apertura_at->toIso8601String(),
                    'cierre_at' => $caja->cierre_at?->toIso8601String(),
                    'estado' => $caja->estado,
                    'cerrada_por_admin' => $caja->cerrada_por_admin,
                    'validada_at' => $caja->validada_at?->toIso8601String(),
                    'motivo_rechazo' => $caja->motivo_rechazo,
                    'es_caja_vieja_abierta' => $esCajaViejaAbierta,
                    'usuario_operativo' => [
                        'id' => $caja->usuarioOperativo->id,
                        'name' => $caja->usuarioOperativo->name,
                    ],
                    'totales_generales' => $resumen['totales_generales'],
                    'cantidad_movimientos' => $resumen['cantidad_movimientos'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => $data->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cajas pendientes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener resumen completo de una caja
     *
     * GET /api/cajas/{id}/resumen
     *
     * Retorna:
     * - Totales por tipo de caja (Efectivo, Banco, MP)
     * - Totales por naturaleza (INGRESO/EGRESO) con desglose por rubro/subrubro
     * - Totales generales (total_ingresos, total_egresos, neto)
     * - Listado de movimientos
     */
    public function resumen(int $id): JsonResponse
    {
        try {
            $resumen = $this->cajaResumenService->resumen($id);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el resumen de la caja.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
