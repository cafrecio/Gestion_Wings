<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use Illuminate\Http\Request;

class NivelWebController extends Controller
{
    public function index()
    {
        $niveles = Nivel::withCount('grupos')->orderBy('nombre')->paginate(20);
        return view('niveles.index', compact('niveles'));
    }

    public function create()
    {
        return view('niveles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100|unique:niveles,nombre',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.unique'   => 'Ya existe un nivel con ese nombre.',
        ]);

        Nivel::create($request->only('nombre', 'descripcion'));

        return redirect()->route('web.niveles.index')->with('success', 'Nivel creado correctamente.');
    }

    public function edit(int $id)
    {
        $nivel = Nivel::findOrFail($id);
        return view('niveles.edit', compact('nivel'));
    }

    public function update(Request $request, int $id)
    {
        $nivel = Nivel::findOrFail($id);

        $request->validate([
            'nombre'      => 'required|string|max:100|unique:niveles,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.unique'   => 'Ya existe un nivel con ese nombre.',
        ]);

        $nivel->update($request->only('nombre', 'descripcion'));

        return redirect()->route('web.niveles.index')->with('success', 'Nivel actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $nivel = Nivel::withCount('grupos')->findOrFail($id);

        if ($nivel->grupos_count > 0) {
            return back()->with('error', "No se puede eliminar: hay {$nivel->grupos_count} grupo(s) que usan este nivel.");
        }

        $nivel->delete();

        return redirect()->route('web.niveles.index')->with('success', 'Nivel eliminado.');
    }
}
