<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRubroRequest;
use App\Http\Requests\Admin\UpdateRubroRequest;
use App\Models\Rubro;
use Illuminate\Http\JsonResponse;

class RubroController extends Controller
{
    public function index(): JsonResponse
    {
        $rubros = Rubro::withCount('subrubros')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rubros,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $rubro = Rubro::with('subrubros')->find($id);

        if (!$rubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Rubro no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rubro,
        ]);
    }

    public function store(StoreRubroRequest $request): JsonResponse
    {
        $rubro = Rubro::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $rubro,
        ], 201);
    }

    public function update(UpdateRubroRequest $request, int $id): JsonResponse
    {
        $rubro = Rubro::find($id);

        if (!$rubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Rubro no encontrado.'],
            ], 404);
        }

        $rubro->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $rubro->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $rubro = Rubro::find($id);

        if (!$rubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Rubro no encontrado.'],
            ], 404);
        }

        if ($rubro->subrubros()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene subrubros asociados.'],
            ], 409);
        }

        $rubro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rubro eliminado.',
        ]);
    }
}
