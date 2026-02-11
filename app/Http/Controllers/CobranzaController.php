<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolverRevisionCobranzaRequest;
use App\Models\AlumnoRevisionCobranza;
use App\Services\CobranzaEstadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CobranzaController extends Controller
{
    private CobranzaEstadoService $cobranzaService;

    public function __construct(CobranzaEstadoService $cobranzaService)
    {
        $this->cobranzaService = $cobranzaService;
    }

    /**
     * Estado de cobranza de un alumno.
     *
     * GET /api/alumnos/{id}/estado-cobranza
     */
    public function estadoAlumno(int $id): JsonResponse
    {
        try {
            $estado = $this->cobranzaService->estadoAlumno($id);

            return response()->json([
                'success' => true,
                'data' => $estado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    /**
     * Listado de alumnos filtrado por estado de cobranza.
     *
     * GET /api/cobranza/alumnos?estado_cobranza=AL_DIA&deporte_id=1&grupo_id=2
     */
    public function alumnosPorEstado(Request $request): JsonResponse
    {
        $estadoCobranza = $request->query('estado_cobranza');
        $deporteId = $request->query('deporte_id');
        $grupoId = $request->query('grupo_id');

        $alumnos = $this->cobranzaService->filtrarAlumnosPorEstado(
            $estadoCobranza,
            $deporteId ? (int) $deporteId : null,
            $grupoId ? (int) $grupoId : null
        );

        return response()->json([
            'success' => true,
            'data' => $alumnos,
            'total' => $alumnos->count(),
        ]);
    }

    /**
     * Dashboard de cobranza.
     *
     * GET /api/admin/cobranza/dashboard
     */
    public function dashboard(): JsonResponse
    {
        $resumen = $this->cobranzaService->resumenDashboard();

        return response()->json([
            'success' => true,
            'data' => $resumen,
        ]);
    }

    /**
     * Listado de revisiones de cobranza pendientes.
     *
     * GET /api/admin/cobranza/revision?estado=PENDIENTE
     */
    public function indexRevision(Request $request): JsonResponse
    {
        $estado = $request->query('estado', 'PENDIENTE');

        $revisiones = AlumnoRevisionCobranza::with('alumno')
            ->where('estado_revision', $estado)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $revisiones,
            'total' => $revisiones->count(),
        ]);
    }

    /**
     * Resolver una revisión de cobranza.
     *
     * POST /api/admin/cobranza/revision/{id}/resolver
     */
    public function resolverRevision(ResolverRevisionCobranzaRequest $request, int $id): JsonResponse
    {
        try {
            $revision = $this->cobranzaService->resolverRevision(
                $id,
                $request->validated()['accion']
            );

            return response()->json([
                'success' => true,
                'message' => 'Revisión resuelta exitosamente.',
                'data' => $revision->load('alumno'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }
}
