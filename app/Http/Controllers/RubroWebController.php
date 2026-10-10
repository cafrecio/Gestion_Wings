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

        if ($rubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: rubro reservado del sistema.');
        }

        return view('rubros.edit', compact('rubro'));
    }

    public function update(Request $request, int $id)
    {
        $rubro = Rubro::findOrFail($id);

        if ($rubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: rubro reservado del sistema.');
        }

        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:255', new NombreUnico(Rubro::class, ignoreId: $rubro->id, mensaje: 'Ya existe un rubro con ese nombre.')],
            'tipo'        => 'required|in:INGRESO,EGRESO',
            'observacion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'tipo.required'   => 'El tipo es obligatorio.',
            'tipo.in'         => 'El tipo debe ser INGRESO o EGRESO.',
        ]);

        // T16 (devolución de Codex): un aporte solo entra y un retiro solo sale. Cambiar el
        // tipo del rubro no puede dejar adentro un subrubro con la clasificación opuesta.
        if ($validated['tipo'] !== $rubro->tipo) {
            $noCorresponden = $rubro->subrubros()->whereNotNull('clasificacion_resultado')
                ->whereNotIn('clasificacion_resultado', \App\Support\ClasificacionSubrubros::permitidas($validated['tipo']))
                ->pluck('nombre');
            if ($noCorresponden->isNotEmpty()) {
                return back()->withInput()->with('error', 'No se puede cambiar el tipo: '.$noCorresponden->join(', ', ' y ')
                    .' está clasificado como plata de los dueños. Cambiá primero qué es ese subrubro.');
            }
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
