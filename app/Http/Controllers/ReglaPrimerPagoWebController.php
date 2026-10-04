<?php

namespace App\Http\Controllers;

use App\Models\ReglaPrimerPago;
use Illuminate\Http\Request;

class ReglaPrimerPagoWebController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validar($request);

        if ($choque = $this->reglaSuperpuesta($validated['dia_desde'], $validated['dia_hasta'])) {
            return $this->errorDeSuperposicion($validated, $choque);
        }

        $regla = ReglaPrimerPago::create($validated);

        return response()->json($regla);
    }

    public function update(Request $request, int $id)
    {
        $regla = ReglaPrimerPago::findOrFail($id);

        $validated = $this->validar($request);

        if ($choque = $this->reglaSuperpuesta($validated['dia_desde'], $validated['dia_hasta'], $regla->id)) {
            return $this->errorDeSuperposicion($validated, $choque);
        }

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

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:100',
            'dia_desde' => 'required|integer|min:1|max:31',
            'dia_hasta' => 'required|integer|min:1|max:31|gte:dia_desde',
            'porcentaje' => 'required|numeric|min:1|max:100',
        ], [
            'nombre.required' => 'Ingresá un nombre para la regla.',
            'nombre.string' => 'Ingresá el nombre como texto.',
            'nombre.max' => 'El nombre puede tener hasta 100 caracteres.',
            'dia_desde.required' => 'Elegí el día desde el que se aplica.',
            'dia_hasta.required' => 'Elegí hasta qué día se aplica.',
            'dia_desde.integer' => 'El día inicial debe ser un número entero del 1 al 31.',
            'dia_hasta.integer' => 'El día final debe ser un número entero del 1 al 31.',
            'dia_desde.min' => 'El día inicial va del 1 al 31.',
            'dia_desde.max' => 'El día inicial va del 1 al 31.',
            'dia_hasta.min' => 'El día final va del 1 al 31.',
            'dia_hasta.max' => 'El día final va del 1 al 31.',
            'dia_hasta.gte' => 'El día final no puede ser anterior al día inicial.',
            'porcentaje.required' => 'Ingresá el porcentaje de la primera cuota.',
            'porcentaje.numeric' => 'Ingresá un porcentaje numérico del 1 al 100.',
            'porcentaje.min' => 'El porcentaje va del 1 al 100.',
            'porcentaje.max' => 'El porcentaje va del 1 al 100.',
        ]);
    }

    /**
     * Devuelve la regla activa que se pisa con el tramo pedido, si la hay.
     *
     * Dos tramos se superponen cuando cada uno empieza antes de que termine el otro.
     */
    private function reglaSuperpuesta(int $desde, int $hasta, ?int $ignorarId = null): ?ReglaPrimerPago
    {
        return ReglaPrimerPago::where('activo', true)
            ->when($ignorarId, fn($q) => $q->where('id', '!=', $ignorarId))
            ->where('dia_desde', '<=', $hasta)
            ->where('dia_hasta', '>=', $desde)
            ->first();
    }

    /**
     * Sin esta validación se podían guardar dos reglas para el mismo día, y el
     * resultado no era que ganara una: `obtenerReglaPorDia()` devuelve una colección
     * y quien la usa solo aplica la regla cuando viene exactamente una
     * (`CajaWebController` al armar el cobro). Con dos tramos encimados **el
     * descuento del primer pago simplemente no aparecía**, sin ningún aviso, y quien
     * cobraba no tenía forma de saber por qué.
     */
    private function errorDeSuperposicion(array $validated, ReglaPrimerPago $choque)
    {
        return response()->json([
            'error' => sprintf(
                'Los días %d a %d se pisan con la regla "%s", que va del %d al %d. '.
                'Un mismo día no puede tener dos porcentajes.',
                $validated['dia_desde'],
                $validated['dia_hasta'],
                $choque->nombre,
                $choque->dia_desde,
                $choque->dia_hasta
            ),
        ], 422);
    }
}
