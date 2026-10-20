<?php

namespace App\Http\Controllers;

use App\Models\ReglaPrimerPago;
use App\Support\ReglasPrimerPagoValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReglaPrimerPagoWebController extends Controller
{
    /**
     * Guarda el conjunto completo de reglas de primer pago en una sola transacción atómica.
     * Valida que cubran del día 1 al 31 sin huecos ni superposiciones.
     */
    public function guardarTodas(Request $request)
    {
        $validated = $request->validate([
            'reglas'               => 'required|array|min:1',
            'reglas.*.id'          => 'nullable|integer|distinct|exists:reglas_primer_pago,id',
            'reglas.*.nombre'      => 'required|string|max:100',
            'reglas.*.dia_desde'   => 'required|integer|min:1|max:31',
            'reglas.*.dia_hasta'   => 'required|integer|min:1|max:31|gte:reglas.*.dia_desde',
            'reglas.*.porcentaje'  => 'required|numeric|min:1|max:100',
        ], [
            'reglas.required'            => 'Debe existir al menos una regla que cubra del día 1 al 31.',
            'reglas.min'                 => 'Debe existir al menos una regla que cubra del día 1 al 31.',
            'reglas.*.id.distinct'       => 'Cada regla debe tener un identificador único; no se puede repetir una misma regla.',
            'reglas.*.id.exists'         => 'Una de las reglas enviadas no existe o ya no está disponible.',
            'reglas.*.nombre.required'   => 'Ingresá un nombre para cada regla.',
            'reglas.*.dia_desde.required'=> 'Elegí el día desde el que se aplica.',
            'reglas.*.dia_hasta.required'=> 'Elegí hasta qué día se aplica.',
            'reglas.*.dia_hasta.gte'     => 'El día final no puede ser anterior al día inicial.',
            'reglas.*.porcentaje.required'=> 'Ingresá el porcentaje de la primera cuota.',
            'reglas.*.porcentaje.min'    => 'El porcentaje va del 1 al 100.',
            'reglas.*.porcentaje.max'    => 'El porcentaje va del 1 al 100.',
        ]);

        if ($error = ReglasPrimerPagoValidator::validar($validated['reglas'])) {
            return response()->json(['error' => $error], 422);
        }

        return DB::transaction(function () use ($validated) {
            $idsEnviados = array_filter(array_column($validated['reglas'], 'id'));

            // Eliminar las que ya no están presentes en el conjunto
            ReglaPrimerPago::where('activo', true)->whereNotIn('id', $idsEnviados)->delete();

            foreach ($validated['reglas'] as $item) {
                if (!empty($item['id'])) {
                    $regla = ReglaPrimerPago::findOrFail($item['id']);
                    $regla->update([
                        'nombre'     => $item['nombre'],
                        'dia_desde'  => (int) $item['dia_desde'],
                        'dia_hasta'  => (int) $item['dia_hasta'],
                        'porcentaje' => (float) $item['porcentaje'],
                        'activo'     => true,
                    ]);
                } else {
                    ReglaPrimerPago::create([
                        'nombre'     => $item['nombre'],
                        'dia_desde'  => (int) $item['dia_desde'],
                        'dia_hasta'  => (int) $item['dia_hasta'],
                        'porcentaje' => (float) $item['porcentaje'],
                        'activo'     => true,
                    ]);
                }
            }

            return response()->json(ReglaPrimerPago::where('activo', true)->orderBy('dia_desde')->get());
        });
    }

    public function store(Request $request)
    {
        if ($request->has('reglas')) {
            return $this->guardarTodas($request);
        }

        $validated = $this->validar($request);

        $existentes = ReglaPrimerPago::where('activo', true)
            ->get()
            ->map(fn($r) => $r->only(['id', 'nombre', 'dia_desde', 'dia_hasta', 'porcentaje']))
            ->toArray();

        $candidatas = array_merge($existentes, [$validated]);

        if ($error = ReglasPrimerPagoValidator::validar($candidatas)) {
            return response()->json(['error' => $error], 422);
        }

        $regla = ReglaPrimerPago::create($validated);

        return response()->json($regla);
    }

    public function update(Request $request, int $id)
    {
        $regla = ReglaPrimerPago::findOrFail($id);

        $validated = $this->validar($request);

        $existentes = ReglaPrimerPago::where('activo', true)->get();
        $candidatas = [];

        foreach ($existentes as $item) {
            if ($item->id === $regla->id) {
                $candidatas[] = array_merge($validated, ['id' => $regla->id]);
            } else {
                $candidatas[] = $item->only(['id', 'nombre', 'dia_desde', 'dia_hasta', 'porcentaje']);
            }
        }

        if ($error = ReglasPrimerPagoValidator::validar($candidatas)) {
            return response()->json(['error' => $error], 422);
        }

        $regla->update($validated);

        return response()->json($regla->fresh());
    }

    public function destroy(int $id)
    {
        $regla = ReglaPrimerPago::findOrFail($id);

        $candidatas = ReglaPrimerPago::where('activo', true)
            ->where('id', '!=', $regla->id)
            ->get()
            ->map(fn($r) => $r->only(['id', 'nombre', 'dia_desde', 'dia_hasta', 'porcentaje']))
            ->toArray();

        if ($error = ReglasPrimerPagoValidator::validar($candidatas)) {
            return response()->json(['error' => $error], 422);
        }

        $regla->delete();

        return response()->json(['ok' => true]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'     => 'required|string|max:100',
            'dia_desde'  => 'required|integer|min:1|max:31',
            'dia_hasta'  => 'required|integer|min:1|max:31|gte:dia_desde',
            'porcentaje' => 'required|numeric|min:1|max:100',
        ], [
            'nombre.required'     => 'Ingresá un nombre para la regla.',
            'nombre.string'       => 'Ingresá el nombre como texto.',
            'nombre.max'          => 'El nombre puede tener hasta 100 caracteres.',
            'dia_desde.required'  => 'Elegí el día desde el que se aplica.',
            'dia_hasta.required'  => 'Elegí hasta qué día se aplica.',
            'dia_desde.integer'   => 'El día inicial debe ser un número entero del 1 al 31.',
            'dia_hasta.integer'   => 'El día final debe ser un número entero del 1 al 31.',
            'dia_desde.min'       => 'El día inicial va del 1 al 31.',
            'dia_desde.max'       => 'El día inicial va del 1 al 31.',
            'dia_hasta.min'       => 'El día final va del 1 al 31.',
            'dia_hasta.max'       => 'El día final va del 1 al 31.',
            'dia_hasta.gte'       => 'El día final no puede ser anterior al día inicial.',
            'porcentaje.required' => 'Ingresá el porcentaje de la primera cuota.',
            'porcentaje.numeric'  => 'Ingresá un porcentaje numérico del 1 al 100.',
            'porcentaje.min'      => 'El porcentaje va del 1 al 100.',
            'porcentaje.max'      => 'El porcentaje va del 1 al 100.',
        ]);
    }
}
