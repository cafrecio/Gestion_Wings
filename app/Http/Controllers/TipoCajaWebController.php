<?php

namespace App\Http\Controllers;

use App\Models\TipoCaja;
use App\Rules\NombreUnico;
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
            'nombre'      => ['required', 'string', 'max:100', new NombreUnico(TipoCaja::class, mensaje: 'Ya existe un tipo de caja con ese nombre.')],
            'abreviatura' => 'required|string|max:5',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'nombre.max'           => 'El nombre no puede tener más de 100 caracteres.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
            'abreviatura.max'      => 'La abreviatura no puede tener más de 5 caracteres.',
        ]);

        TipoCaja::create([
            'nombre'              => $request->nombre,
            'abreviatura'         => strtoupper($request->abreviatura),
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
            'nombre'      => ['required', 'string', 'max:100', new NombreUnico(TipoCaja::class, ignoreId: $tipoCaja->id, mensaje: 'Ya existe un tipo de caja con ese nombre.')],
            'abreviatura' => 'required|string|max:5',
            'descripcion' => 'nullable|string|max:255',
        ], [
            'nombre.required'      => 'El nombre es obligatorio.',
            'nombre.max'           => 'El nombre no puede tener más de 100 caracteres.',
            'abreviatura.required' => 'La abreviatura es obligatoria.',
            'abreviatura.max'      => 'La abreviatura no puede tener más de 5 caracteres.',
        ]);

        $tipoCaja->update([
            'nombre'              => $request->nombre,
            'abreviatura'         => strtoupper($request->abreviatura),
            'descripcion'         => $request->descripcion,
            'permite_descubierto' => $request->boolean('permite_descubierto'),
        ]);

        return redirect()->route('web.tipos-caja.index')
            ->with('success', 'Tipo de caja actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $tipoCaja = TipoCaja::findOrFail($id);
        $tipoCaja->update(['activo' => !$tipoCaja->activo]);

        $estado = $tipoCaja->activo ? 'activado' : 'desactivado';
        return redirect()->route('web.tipos-caja.index')
            ->with('success', "Tipo de caja {$estado}.");
    }

    public function checkDisponible(Request $request)
    {
        $nombre     = trim($request->input('nombre', ''));
        $tipoCajaId = $request->input('tipo_caja_id');

        if ($nombre === '') {
            return response()->json(['disponible' => true]);
        }

        $existe = NombreUnico::existe(TipoCaja::class, $nombre, $tipoCajaId ? (int) $tipoCajaId : null);

        return response()->json(['disponible' => !$existe]);
    }
}
