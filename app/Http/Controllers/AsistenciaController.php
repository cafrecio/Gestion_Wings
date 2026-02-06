<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaRequest;
use App\Http\Requests\StoreAsistenciaBulkRequest;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Services\ClaseService;
use Illuminate\Http\JsonResponse;

class AsistenciaController extends Controller
{
    protected ClaseService $claseService;

    public function __construct(ClaseService $claseService)
    {
        $this->claseService = $claseService;
    }

    /**
     * Listar asistencias de una clase
     *
     * GET /api/asistencias/clase/{claseId}
     */
    public function indexByClase(int $claseId): JsonResponse
    {
        $clase = Clase::find($claseId);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        $asistencias = Asistencia::with('alumno')
            ->where('clase_id', $claseId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'clase_id' => $claseId,
                'fecha' => $clase->fecha,
                'asistencias' => $asistencias,
                'total' => $asistencias->count(),
                'presentes' => $asistencias->where('presente', true)->count(),
            ],
        ]);
    }

    /**
     * Registrar asistencias bulk para una clase
     *
     * POST /api/asistencias/clase/{claseId}
     */
    public function storeBulk(StoreAsistenciaBulkRequest $request, int $claseId): JsonResponse
    {
        $clase = Clase::find($claseId);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        if ($clase->cancelada) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CLASE_CANCELADA', 'message' => 'No se pueden registrar asistencias en una clase cancelada.'],
            ], 409);
        }

        $items = $request->validated()['items'];
        $registradas = [];

        foreach ($items as $item) {
            $asistencia = Asistencia::updateOrCreate(
                [
                    'clase_id' => $claseId,
                    'alumno_id' => $item['alumno_id'],
                ],
                [
                    'presente' => $item['presente'],
                ]
            );
            $registradas[] = $asistencia;
        }

        return response()->json([
            'success' => true,
            'message' => 'Asistencias registradas.',
            'data' => [
                'clase_id' => $claseId,
                'registradas' => count($registradas),
            ],
        ], 201);
    }

    /**
     * Store a newly created asistencia in storage.
     *
     * Registra la asistencia de un alumno a una clase.
     * Valida que el alumno no tenga otra asistencia solapada si se marca como presente.
     */
    public function store(StoreAsistenciaRequest $request): JsonResponse
    {
        try {
            $asistencia = $this->claseService->registrarAsistencia(
                $request->validated()['clase_id'],
                $request->validated()['alumno_id'],
                $request->validated()['presente']
            );

            $asistencia->load(['clase', 'alumno']);

            return response()->json([
                'success' => true,
                'message' => 'Asistencia registrada exitosamente.',
                'data' => $asistencia,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la asistencia.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar si un alumno puede asistir a una clase.
     */
    public function verificarDisponibilidadAlumno(int $claseId, int $alumnoId): JsonResponse
    {
        $resultado = $this->claseService->verificarDisponibilidadAlumno($claseId, $alumnoId);

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }
}
