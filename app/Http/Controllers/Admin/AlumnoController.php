<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlumnoRequest;
use App\Http\Requests\Admin\UpdateAlumnoRequest;
use App\Models\Alumno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumnoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alumno::with(['deporte', 'grupo']);

        if ($request->has('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        if ($request->has('grupo_id')) {
            $query->where('grupo_id', $request->input('grupo_id'));
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $alumnos,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $alumno = Alumno::with(['deporte', 'grupo', 'planActivo', 'deudaCuotas'])
            ->find($id);

        if (!$alumno) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Alumno no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $alumno,
        ]);
    }

    public function store(StoreAlumnoRequest $request): JsonResponse
    {
        $alumno = Alumno::create($request->validated());
        $alumno->load(['deporte', 'grupo']);

        return response()->json([
            'success' => true,
            'data' => $alumno,
        ], 201);
    }

    public function update(UpdateAlumnoRequest $request, int $id): JsonResponse
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Alumno no encontrado.'],
            ], 404);
        }

        $alumno->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $alumno->fresh()->load(['deporte', 'grupo']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Alumno no encontrado.'],
            ], 404);
        }

        if ($alumno->tienePagos()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene pagos registrados.'],
            ], 409);
        }

        if ($alumno->asistencias()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene asistencias registradas.'],
            ], 409);
        }

        $alumno->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alumno eliminado.',
        ]);
    }
}
