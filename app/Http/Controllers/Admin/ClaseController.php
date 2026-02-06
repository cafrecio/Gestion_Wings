<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClaseRequest;
use App\Http\Requests\Admin\UpdateClaseRequest;
use App\Models\Clase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Clase::with(['grupo.deporte', 'profesores', 'asistencias']);

        if ($request->has('fecha')) {
            $query->where('fecha', $request->input('fecha'));
        }

        if ($request->has('grupo_id')) {
            $query->where('grupo_id', $request->input('grupo_id'));
        }

        if ($request->has('desde') && $request->has('hasta')) {
            $query->whereBetween('fecha', [$request->input('desde'), $request->input('hasta')]);
        }

        $clases = $query->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clases,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $clase = Clase::with(['grupo.deporte', 'profesores', 'asistencias.alumno'])
            ->find($id);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $clase,
        ]);
    }

    public function store(StoreClaseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $profesorIds = $data['profesor_ids'] ?? [];
        unset($data['profesor_ids']);

        $clase = Clase::create($data);

        if (!empty($profesorIds)) {
            $clase->profesores()->attach($profesorIds);
        }

        $clase->load(['grupo.deporte', 'profesores']);

        return response()->json([
            'success' => true,
            'data' => $clase,
        ], 201);
    }

    public function update(UpdateClaseRequest $request, int $id): JsonResponse
    {
        $clase = Clase::find($id);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        $data = $request->validated();
        $profesorIds = $data['profesor_ids'] ?? null;
        unset($data['profesor_ids']);

        $clase->update($data);

        if ($profesorIds !== null) {
            $clase->profesores()->sync($profesorIds);
        }

        return response()->json([
            'success' => true,
            'data' => $clase->fresh()->load(['grupo.deporte', 'profesores']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $clase = Clase::find($id);

        if (!$clase) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Clase no encontrada.'],
            ], 404);
        }

        if ($clase->validada_para_liquidacion) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'ALREADY_VALIDATED', 'message' => 'No se puede eliminar: clase ya validada para liquidación.'],
            ], 409);
        }

        if ($clase->asistencias()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene asistencias registradas.'],
            ], 409);
        }

        $clase->profesores()->detach();
        $clase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Clase eliminada.',
        ]);
    }
}
