<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubrubroRequest;
use App\Http\Requests\Admin\UpdateSubrubroRequest;
use App\Models\Subrubro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubrubroController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Subrubro::with('rubro');

        if ($request->has('rubro_id')) {
            $query->where('rubro_id', $request->input('rubro_id'));
        }

        $subrubros = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $subrubros,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $subrubro = Subrubro::with('rubro')->find($id);

        if (!$subrubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Subrubro no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subrubro,
        ]);
    }

    public function store(StoreSubrubroRequest $request): JsonResponse
    {
        $subrubro = Subrubro::create($request->validated());
        $subrubro->load('rubro');

        return response()->json([
            'success' => true,
            'data' => $subrubro,
        ], 201);
    }

    public function update(UpdateSubrubroRequest $request, int $id): JsonResponse
    {
        $subrubro = Subrubro::find($id);

        if (!$subrubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Subrubro no encontrado.'],
            ], 404);
        }

        if ($subrubro->es_reservado_sistema) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'RESERVED', 'message' => 'No se puede modificar: subrubro reservado del sistema.'],
            ], 409);
        }

        $subrubro->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $subrubro->fresh()->load('rubro'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $subrubro = Subrubro::find($id);

        if (!$subrubro) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Subrubro no encontrado.'],
            ], 404);
        }

        if ($subrubro->es_reservado_sistema) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'RESERVED', 'message' => 'No se puede eliminar: subrubro reservado del sistema.'],
            ], 409);
        }

        if ($subrubro->movimientosOperativos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene movimientos operativos asociados.'],
            ], 409);
        }

        if ($subrubro->cashflowMovimientos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene movimientos de cashflow asociados.'],
            ], 409);
        }

        $subrubro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subrubro eliminado.',
        ]);
    }
}
