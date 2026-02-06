<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTipoCajaRequest;
use App\Http\Requests\Admin\UpdateTipoCajaRequest;
use App\Models\TipoCaja;
use Illuminate\Http\JsonResponse;

class TipoCajaController extends Controller
{
    public function index(): JsonResponse
    {
        $tiposCaja = TipoCaja::orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $tiposCaja,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tipoCaja = TipoCaja::find($id);

        if (!$tipoCaja) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Tipo de caja no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tipoCaja,
        ]);
    }

    public function store(StoreTipoCajaRequest $request): JsonResponse
    {
        $tipoCaja = TipoCaja::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $tipoCaja,
        ], 201);
    }

    public function update(UpdateTipoCajaRequest $request, int $id): JsonResponse
    {
        $tipoCaja = TipoCaja::find($id);

        if (!$tipoCaja) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Tipo de caja no encontrado.'],
            ], 404);
        }

        $tipoCaja->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $tipoCaja->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tipoCaja = TipoCaja::find($id);

        if (!$tipoCaja) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Tipo de caja no encontrado.'],
            ], 404);
        }

        if ($tipoCaja->movimientosOperativos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene movimientos operativos asociados.'],
            ], 409);
        }

        if ($tipoCaja->cashflowMovimientos()->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'HAS_REFERENCES', 'message' => 'No se puede eliminar: tiene movimientos de cashflow asociados.'],
            ], 409);
        }

        $tipoCaja->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de caja eliminado.',
        ]);
    }
}
