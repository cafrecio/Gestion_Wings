<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use App\Models\Subrubro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubrubroWebController extends Controller
{
    public function create(int $rubroId)
    {
        $rubro = Rubro::findOrFail($rubroId);

        return view('subrubros.create', compact('rubro'));
    }

    public function store(Request $request, int $rubroId)
    {
        $rubro = Rubro::findOrFail($rubroId);

        $validated = $request->validate([
            'nombre'        => 'required|string|max:255|unique:subrubros,nombre',
            'permitido_para' => 'nullable|in:ADMIN,OPERATIVO',
            'afecta_caja'   => 'boolean',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe un subrubro con ese nombre.',
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
            'nombre'         => ['required', 'string', 'max:255', Rule::unique('subrubros', 'nombre')->ignore($subrubro->id)],
            'permitido_para' => 'nullable|in:ADMIN,OPERATIVO',
            'afecta_caja'    => 'boolean',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique'   => 'Ya existe un subrubro con ese nombre.',
        ]);

        $validated['afecta_caja'] = $request->boolean('afecta_caja');

        $subrubro->update($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro actualizado correctamente.');
    }

    public function destroy(int $rubroId, int $id)
    {
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede eliminar: subrubro reservado del sistema.');
        }

        $subrubro->delete();

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro eliminado correctamente.');
    }
}
