<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGrupoRequest;
use App\Http\Requests\Admin\UpdateGrupoRequest;
use App\Models\Grupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Grupo::with('deporte')
            ->withCount(['alumnos', 'clases']);

        if ($request->has('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $grupos = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $grupos,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $grupo = Grupo::with(['deporte', 'alumnos', 'planes'])
            ->find($id);

        if (!$grupo) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Grupo no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $grupo,
        ]);
    }

    public function store(StoreGrupoRequest $request): JsonResponse
    {
        $grupo = Grupo::create($request->validated());
        $grupo->load('deporte');

        return response()->json([
            'success' => true,
            'data' => $grupo,
        ], 201);
    }

    public function update(UpdateGrupoRequest $request, int $id): JsonResponse
    {
        $grupo = Grupo::find($id);

        if (!$grupo) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Grupo no encontrado.'],
            ], 404);
        }

        $grupo->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $grupo->fresh()->load('deporte'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $grupo = Grupo::find($id);

        if (!$grupo) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Grupo no encontrado.'],
            ], 404);
        }

        if ($grupo->alumnos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene alumnos asociados.'],
            ], 409);
        }

        if ($grupo->clases()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene clases asociadas.'],
            ], 409);
        }

        $grupo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo eliminado.',
        ]);
    }
}
