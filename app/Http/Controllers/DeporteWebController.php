<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Clase;
use App\Models\Deporte;
use App\Rules\NombreUnico;
use Illuminate\Http\Request;

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
            'nombre'           => ['required', 'string', 'max:255', new NombreUnico(Deporte::class, mensaje: 'Ya existe un deporte con ese nombre.')],
            'tipo_liquidacion' => 'required|in:HORA,COMISION',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
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
            'nombre'           => ['required', 'string', 'max:255', new NombreUnico(Deporte::class, ignoreId: $deporte->id, mensaje: 'Ya existe un deporte con ese nombre.')],
            'tipo_liquidacion' => 'required|in:HORA,COMISION',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'tipo_liquidacion.required' => 'Debe seleccionar el tipo de liquidación.',
            'tipo_liquidacion.in'       => 'El tipo de liquidación no es válido.',
        ]);

        $cambio = app(\App\Services\DeporteLiquidacionService::class)->actualizar(
            $id, $validated['nombre'], $validated['tipo_liquidacion'], $request->user()
        );
        return redirect()->route($cambio ? 'web.deportes.edit' : 'web.deportes.index', $cambio ? ['id' => $id] : [])
            ->with('success', $cambio ? 'Cambio programado desde el '.today()->startOfMonth()->addMonth()->format('d/m/Y').'.' : 'Deporte actualizado correctamente.');
    }

    public function toggleActivo(Request $request, int $id)
    {
        $deporte = Deporte::findOrFail($id);

        if ($deporte->activo) {
            $alumnosActivos = Alumno::where('activo', true)
                ->where(function ($q) use ($deporte) {
                    $q->where('deporte_id', $deporte->id)
                      ->orWhereIn('grupo_id', $deporte->grupos()->select('id'));
                })
                ->count();

            $clasesFuturas = Clase::whereHas('grupo', fn ($q) => $q->where('deporte_id', $deporte->id))
                ->where('cancelada', false)
                ->whereDate('fecha', '>=', today())
                ->count();

            if ($alumnosActivos > 0 || $clasesFuturas > 0) {
                $partes = [];
                if ($alumnosActivos > 0) {
                    $partes[] = $alumnosActivos === 1 ? '1 alumno activo' : "{$alumnosActivos} alumnos activos";
                }
                if ($clasesFuturas > 0) {
                    $partes[] = $clasesFuturas === 1 ? '1 clase futura' : "{$clasesFuturas} clases futuras";
                }
                $motivo = implode(' y ', $partes);
                $mensaje = "No se puede desactivar el deporte porque tiene {$motivo}.";

                if ($request->expectsJson()) {
                    return response()->json(['error' => $mensaje], 422);
                }

                return back()->with('error', $mensaje);
            }
        }

        $deporte->update(['activo' => !$deporte->activo]);

        if ($request->expectsJson()) {
            return response()->json(['activo' => (bool) $deporte->activo]);
        }

        $estado = $deporte->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Deporte {$estado} correctamente.");
    }
}
