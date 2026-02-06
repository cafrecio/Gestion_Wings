<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDeporteRequest;
use App\Http\Requests\Admin\UpdateDeporteRequest;
use App\Models\Deporte;
use Illuminate\Http\JsonResponse;

class DeporteController extends Controller
{
    public function index(): JsonResponse
    {
        $deportes = Deporte::withCount(['grupos', 'profesores', 'alumnos'])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deportes,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $deporte = Deporte::with(['grupos', 'profesores'])
            ->find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Deporte no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deporte,
        ]);
    }

    public function store(StoreDeporteRequest $request): JsonResponse
    {
        $deporte = Deporte::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $deporte,
        ], 201);
    }

    public function update(UpdateDeporteRequest $request, int $id): JsonResponse
    {
        $deporte = Deporte::find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Deporte no encontrado.'],
            ], 404);
        }

        $deporte->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $deporte->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deporte = Deporte::find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Deporte no encontrado.'],
            ], 404);
        }

        // Verificar referencias
        if ($deporte->grupos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene grupos asociados.'],
            ], 409);
        }

        if ($deporte->profesores()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene profesores asociados.'],
            ], 409);
        }

        if ($deporte->alumnos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene alumnos asociados.'],
            ], 409);
        }

        $deporte->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deporte eliminado.',
        ]);
    }
}
