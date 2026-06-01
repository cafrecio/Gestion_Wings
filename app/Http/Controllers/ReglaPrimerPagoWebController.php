<?php

namespace App\Http\Controllers;

use App\Models\ReglaPrimerPago;
use Illuminate\Http\Request;

class ReglaPrimerPagoWebController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'dia_desde'  => 'required|integer|min:1|max:31',
            'dia_hasta'  => 'required|integer|min:1|max:31|gte:dia_desde',
            'porcentaje' => 'required|numeric|min:1|max:100',
        ]);

        $regla = ReglaPrimerPago::create($validated);

        return response()->json($regla);
    }

    public function update(Request $request, int $id)
    {
        $regla = ReglaPrimerPago::findOrFail($id);

        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'dia_desde'  => 'required|integer|min:1|max:31',
            'dia_hasta'  => 'required|integer|min:1|max:31|gte:dia_desde',
            'porcentaje' => 'required|numeric|min:1|max:100',
        ]);

        $regla->update($validated);

        return response()->json($regla->fresh());
    }

    public function destroy(int $id)
    {
        $regla = ReglaPrimerPago::findOrFail($id);

        if (ReglaPrimerPago::count() <= 1) {
            return response()->json(['error' => 'Debe existir al menos una regla.'], 422);
        }

        $regla->delete();

        return response()->json(['ok' => true]);
    }
}
