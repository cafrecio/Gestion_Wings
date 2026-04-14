<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeporteWebController extends Controller
{
    public function index()
    {
        $deportes = Deporte::orderBy('nombre')->get();

        return view('deportes.index', compact('deportes'));
    }

    public function create()
    {
        return view('deportes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255|unique:deportes,nombre',
            'tipo_liquidacion' => 'required|in:HORA,COMISION',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'nombre.unique'             => 'Ya existe un deporte con ese nombre.',
            'tipo_liquidacion.required' => 'Debe seleccionar el tipo de liquidación.',
            'tipo_liquidacion.in'       => 'El tipo de liquidación no es válido.',
        ]);

        $validated['activo'] = true;

        Deporte::create($validated);

        return redirect()->route('web.deportes.index')->with('success', 'Deporte creado correctamente.');
    }

    public function edit(int $id)
    {
        $deporte = Deporte::findOrFail($id);

        return view('deportes.edit', compact('deporte'));
    }

    public function update(Request $request, int $id)
    {
        $deporte = Deporte::findOrFail($id);

        $validated = $request->validate([
            'nombre'           => ['required', 'string', 'max:255', Rule::unique('deportes', 'nombre')->ignore($deporte->id)],
            'tipo_liquidacion' => 'required|in:HORA,COMISION',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'nombre.unique'             => 'Ya existe un deporte con ese nombre.',
            'tipo_liquidacion.required' => 'Debe seleccionar el tipo de liquidación.',
            'tipo_liquidacion.in'       => 'El tipo de liquidación no es válido.',
        ]);

        $deporte->update($validated);

        return redirect()->route('web.deportes.index')->with('success', 'Deporte actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $deporte = Deporte::findOrFail($id);
        $deporte->update(['activo' => !$deporte->activo]);

        $estado = $deporte->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Deporte {$estado} correctamente.");
    }
}
