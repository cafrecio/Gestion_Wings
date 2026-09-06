<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use App\Rules\NombreUnico;
use Illuminate\Http\Request;

class RubroWebController extends Controller
{
    public function index()
    {
        $rubros = Rubro::with('subrubros')->orderBy('nombre')->get();

        return view('rubros.index', compact('rubros'));
    }

    public function create()
    {
        return view('rubros.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', new NombreUnico(Rubro::class, mensaje: 'Ya existe un rubro con ese nombre.')],
            'tipo'        => 'required|in:INGRESO,EGRESO',
            'observacion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo.required'   => 'El tipo es obligatorio.',
            'tipo.in'         => 'El tipo debe ser INGRESO o EGRESO.',
        ]);

        Rubro::create($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Rubro creado correctamente.');
    }

    public function edit(int $id)
    {
        $rubro = Rubro::findOrFail($id);

        return view('rubros.edit', compact('rubro'));
    }

    public function update(Request $request, int $id)
    {
        $rubro = Rubro::findOrFail($id);

        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', new NombreUnico(Rubro::class, ignoreId: $rubro->id, mensaje: 'Ya existe un rubro con ese nombre.')],
            'tipo'        => 'required|in:INGRESO,EGRESO',
            'observacion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo.required'   => 'El tipo es obligatorio.',
            'tipo.in'         => 'El tipo debe ser INGRESO o EGRESO.',
        ]);

        // Un rubro reservado lo busca el código por nombre exacto: si se
        // renombra, el alta de profesores y la de usuarios operativos dejan
        // de encontrarlo en silencio. El tipo tampoco se toca, porque da
        // vuelta el signo del rubro en el cashflow. La observación sí.
        if ($rubro->es_reservado_sistema) {
            if ($validated['nombre'] !== $rubro->nombre || $validated['tipo'] !== $rubro->tipo) {
                return back()->withInput()
                    ->with('error', 'Rubro reservado del sistema: no se puede cambiar el nombre ni el tipo.');
            }

            $validated = ['observacion' => $validated['observacion'] ?? null];
        }

        $rubro->update($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Rubro actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $rubro = Rubro::withCount('subrubros')->findOrFail($id);

        if ($rubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede eliminar: rubro reservado del sistema.');
        }

        if ($rubro->subrubros_count > 0) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede eliminar: tiene subrubros asociados.');
        }

        $rubro->delete();

        return redirect()->route('web.rubros.index')->with('success', 'Rubro eliminado correctamente.');
    }
}
