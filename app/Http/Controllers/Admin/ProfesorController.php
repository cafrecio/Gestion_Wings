<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfesorRequest;
use App\Http\Requests\Admin\UpdateProfesorRequest;
use App\Models\Profesor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfesorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Profesor::with('deporte');

        if ($request->has('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $profesores = $query->orderBy('apellido')->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $profesores,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $profesor = Profesor::with(['deporte', 'clases', 'liquidaciones'])
            ->find($id);

        if (!$profesor) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Profesor no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profesor,
        ]);
    }

    public function store(StoreProfesorRequest $request): JsonResponse
    {
        $profesor = Profesor::create($request->validated());
        $profesor->load('deporte');

        return response()->json([
            'success' => true,
            'data' => $profesor,
        ], 201);
    }

    public function update(UpdateProfesorRequest $request, int $id): JsonResponse
    {
        $profesor = Profesor::find($id);

        if (!$profesor) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Profesor no encontrado.'],
            ], 404);
        }

        $profesor->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $profesor->fresh()->load('deporte'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $profesor = Profesor::find($id);

        if (!$profesor) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Profesor no encontrado.'],
            ], 404);
        }

        if ($profesor->clases()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene clases asociadas.'],
            ], 409);
        }

        if ($profesor->liquidaciones()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene liquidaciones asociadas.'],
            ], 409);
        }

        $profesor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profesor eliminado.',
        ]);
    }
}
