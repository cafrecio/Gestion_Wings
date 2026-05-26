<?php

namespace App\Http\Controllers;

use App\Models\TipoCaja;
use Illuminate\Http\Request;

class TipoCajaWebController extends Controller
{
    public function index()
    {
        $tiposCaja = TipoCaja::withCount(['movimientosOperativos', 'cashflowMovimientos'])
            ->orderBy('nombre')
            ->paginate(20);

        return view('tipos-caja.index', compact('tiposCaja'));
    }

    public function create()
    {
        return view('tipos-caja.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:100|unique:tipos_caja,nombre',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo de caja con ese nombre.',
        ]);

        TipoCaja::create([
            'nombre'              => $request->nombre,
            'descripcion'         => $request->descripcion,
            'permite_descubierto' => $request->boolean('permite_descubierto'),
        ]);

        return redirect()->route('web.tipos-caja.index')
            ->with('success', 'Tipo de caja creado correctamente.');
    }

    public function edit(int $id)
    {
        $tipoCaja = TipoCaja::findOrFail($id);
        return view('tipos-caja.edit', compact('tipoCaja'));
    }

    public function update(Request $request, int $id)
    {
        $tipoCaja = TipoCaja::findOrFail($id);

        $request->validate([
            'nombre'      => "required|string|max:100|unique:tipos_caja,nombre,{$tipoCaja->id}",
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no puede tener más de 100 caracteres.',
            'nombre.unique'   => 'Ya existe un tipo de caja con ese nombre.',
        ]);

        $tipoCaja->update([
            'nombre'              => $request->nombre,
            'descripcion'         => $request->descripcion,
            'permite_descubierto' => $request->boolean('permite_descubierto'),
        ]);

        return redirect()->route('web.tipos-caja.index')
            ->with('success', 'Tipo de caja actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $tipoCaja = TipoCaja::withCount(['movimientosOperativos', 'cashflowMovimientos'])
            ->findOrFail($id);

        if ($tipoCaja->movimientos_operativos_count > 0 || $tipoCaja->cashflow_movimientos_count > 0) {
            return back()->with('error', 'No se puede eliminar: tiene movimientos asociados.');
        }

        $tipoCaja->delete();

        return redirect()->route('web.tipos-caja.index')
            ->with('success', 'Tipo de caja eliminado.');
    }

    public function checkDisponible(Request $request)
    {
        $nombre     = trim($request->input('nombre', ''));
        $tipoCajaId = $request->input('tipo_caja_id');

        if ($nombre === '') {
            return response()->json(['disponible' => true]);
        }

        $existe = TipoCaja::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
            ->when($tipoCajaId, fn($q) => $q->where('id', '!=', $tipoCajaId))
            ->exists();

        return response()->json(['disponible' => !$existe]);
    }
}
