<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use App\Models\Subrubro;
use App\Rules\NombreUnico;
use Illuminate\Http\Request;

class SubrubroWebController extends Controller
{
    public function create(int $rubroId)
    {
        $rubro = Rubro::findOrFail($rubroId);

        return view('subrubros.create', compact('rubro'));
    }

    public function store(Request $request, int $rubroId)
    {
        $rubro = Rubro::with('subrubros')->findOrFail($rubroId);

        if ($rubro->subrubros->isNotEmpty() && $rubro->subrubros->every(fn($s) => $s->es_reservado_sistema)) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'Este rubro es administrado por el sistema y no permite subrubros adicionales.');
        }

        $validated = $request->validate([
            'nombre'         => ['required', 'string', 'max:255', new NombreUnico(Subrubro::class, mensaje: 'Ya existe un subrubro con ese nombre.')],
            'permitido_para' => 'required|in:ADMIN,OPERATIVO',
        ], [
            'nombre.required'         => 'El nombre es obligatorio.',
            'permitido_para.required' => 'Elegí quién puede usar el subrubro.',
        ]);

        $validated['rubro_id']    = $rubro->id;
        $validated['afecta_caja'] = $request->boolean('afecta_caja');

        Subrubro::create($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro creado correctamente.');
    }

    public function edit(int $rubroId, int $id)
    {
        $rubro    = Rubro::findOrFail($rubroId);
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: subrubro reservado del sistema.');
        }

        return view('subrubros.edit', compact('rubro', 'subrubro'));
    }

    public function update(Request $request, int $rubroId, int $id)
    {
        $rubro    = Rubro::findOrFail($rubroId);
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: subrubro reservado del sistema.');
        }

        $validated = $request->validate([
            'nombre'         => ['required', 'string', 'max:255', new NombreUnico(Subrubro::class, ignoreId: $subrubro->id, mensaje: 'Ya existe un subrubro con ese nombre.')],
            'permitido_para' => 'required|in:ADMIN,OPERATIVO',
        ], [
            'nombre.required'         => 'El nombre es obligatorio.',
            'permitido_para.required' => 'Elegí quién puede usar el subrubro.',
        ]);

        $validated['afecta_caja'] = $request->boolean('afecta_caja');

        $subrubro->update($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro actualizado correctamente.');
    }

    public function toggleActivo(int $rubroId, int $id)
    {
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede desactivar: subrubro reservado del sistema.');
        }

        $subrubro->update(['activo' => !$subrubro->activo]);

        $estado = $subrubro->activo ? 'activado' : 'desactivado';
        return redirect()->route('web.rubros.index')->with('success', "Subrubro {$estado} correctamente.");
    }
}
