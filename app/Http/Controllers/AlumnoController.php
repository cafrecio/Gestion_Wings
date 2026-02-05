<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Http\Requests\StoreAlumnoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Crea un nuevo alumno con validaciones de tutor según si es menor de edad.
     * Los campos fecha_alta, es_menor y activo se setean automáticamente en el modelo.
     */
    public function store(StoreAlumnoRequest $request): JsonResponse
    {
        try {
            // Los datos ya vienen validados del StoreAlumnoRequest
            // El modelo se encarga de:
            // - Calcular es_menor automáticamente
            // - Setear fecha_alta si no viene
            // - Setear activo = true por defecto
            // - Nullear campos de tutor si es mayor de edad
            $alumno = Alumno::create($request->validated());

            // Cargar relaciones para la respuesta
            $alumno->load(['deporte', 'grupo']);

            return response()->json([
                'success' => true,
                'message' => 'Alumno creado exitosamente.',
                'data' => $alumno,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el alumno.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
