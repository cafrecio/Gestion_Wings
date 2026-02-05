<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaRequest;
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
