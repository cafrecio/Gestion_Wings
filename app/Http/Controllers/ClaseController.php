<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignarProfesorRequest;
use App\Http\Requests\StoreClaseRequest;
use App\Services\ClaseService;
use Illuminate\Http\JsonResponse;

class ClaseController extends Controller
{
    protected ClaseService $claseService;

    public function __construct(ClaseService $claseService)
    {
        $this->claseService = $claseService;
    }

    /**
     * Store a newly created clase in storage.
     *
     * Crea una nueva clase para un grupo en una fecha y horario específico.
     * Si no se provee hora_fin, se calcula como hora_inicio + 1 hora.
     */
    public function store(StoreClaseRequest $request): JsonResponse
    {
        try {
            $clase = $this->claseService->crearClase(
                $request->validated()['grupo_id'],
                $request->validated()['fecha'],
                $request->validated()['hora_inicio'],
                $request->validated()['hora_fin'] ?? null
            );

            $clase->load(['grupo', 'profesores']);

            return response()->json([
                'success' => true,
                'message' => 'Clase creada exitosamente.',
                'data' => $clase,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la clase.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar un profesor a una clase.
     *
     * Valida que el profesor no tenga otra clase solapada en el mismo horario.
     */
    public function asignarProfesor(AsignarProfesorRequest $request): JsonResponse
    {
        try {
            $claseProfesor = $this->claseService->asignarProfesor(
                $request->validated()['clase_id'],
                $request->validated()['profesor_id']
            );

            $claseProfesor->load(['clase', 'profesor']);

            return response()->json([
                'success' => true,
                'message' => 'Profesor asignado exitosamente a la clase.',
                'data' => $claseProfesor,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el profesor.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verificar si un profesor puede ser asignado a una clase.
     */
    public function verificarDisponibilidadProfesor(int $claseId, int $profesorId): JsonResponse
    {
        $resultado = $this->claseService->verificarDisponibilidadProfesor($claseId, $profesorId);

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }
}
