<?php

namespace App\Http\Controllers;

use App\Models\Rubro;
use App\Models\Subrubro;
use App\Rules\NombreUnico;
use App\Support\ClasificacionSubrubros;
use App\Support\RegistroReporteDisponible;
use Illuminate\Http\Request;

class SubrubroWebController extends Controller
{
    public function create(int $rubroId)
    {
        $rubro = Rubro::findOrFail($rubroId);

        if ($rubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'Este rubro es administrado por el sistema y no permite subrubros adicionales.');
        }

        return view('subrubros.create', compact('rubro'));
    }

    public function store(Request $request, int $rubroId)
    {
        $rubro = Rubro::with('subrubros')->findOrFail($rubroId);

        // Un rubro del sistema no acepta subrubros a mano, ni siquiera vacio: sus
        // subrubros los crea el propio sistema. Antes solo se miraba si todos los
        // subrubros existentes eran reservados, asi que `Sueldos` recien instalado
        // —sin ningun profesor cargado todavia— dejaba meterle uno cualquiera.
        // Lo que el admin necesite aparte va en un rubro propio: Honorarios,
        // Alquileres, Gastos Varios.
        if ($rubro->es_reservado_sistema
            || ($rubro->subrubros->isNotEmpty() && $rubro->subrubros->every(fn($s) => $s->es_reservado_sistema))) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'Este rubro es administrado por el sistema y no permite subrubros adicionales.');
        }

        $validated = $request->validate([
            'nombre'         => ['required', 'string', 'max:255', new NombreUnico(Subrubro::class, mensaje: 'Ya existe un subrubro con ese nombre.')],
            'permitido_para' => 'required|in:ADMIN,OPERATIVO',
            ...$this->reglaClasificacion($rubro),
        ], $this->mensajes());

        $validated['rubro_id']    = $rubro->id;
        $validated['afecta_caja'] = $request->boolean('afecta_caja');

        Subrubro::create($validated);

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro creado correctamente.');
    }

    public function edit(int $rubroId, int $id)
    {
        $rubro    = Rubro::findOrFail($rubroId);
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($rubro->es_reservado_sistema || $subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: subrubro reservado del sistema.');
        }

        return view('subrubros.edit', compact('rubro', 'subrubro'));
    }

    public function update(Request $request, int $rubroId, int $id)
    {
        $rubro    = Rubro::findOrFail($rubroId);
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($rubro->es_reservado_sistema || $subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede editar: subrubro reservado del sistema.');
        }

        $validated = $request->validate([
            'nombre'         => ['required', 'string', 'max:255', new NombreUnico(Subrubro::class, ignoreId: $subrubro->id, mensaje: 'Ya existe un subrubro con ese nombre.')],
            'permitido_para' => 'required|in:ADMIN,OPERATIVO',
            ...$this->reglaClasificacion($rubro),
        ], $this->mensajes());

        $validated['afecta_caja'] = $request->boolean('afecta_caja');

        $subrubro->update($validated);

        // Lo registrado mientras el subrubro no tenía clasificación empieza a contar.
        if (RegistroReporteDisponible::existe()) {
            ClasificacionSubrubros::completarMovimientos($subrubro->id);
        }

        return redirect()->route('web.rubros.index')->with('success', 'Subrubro actualizado correctamente.');
    }

    public function toggleActivo(int $rubroId, int $id)
    {
        $subrubro = Subrubro::where('rubro_id', $rubroId)->findOrFail($id);

        if ($subrubro->rubro->es_reservado_sistema || $subrubro->es_reservado_sistema) {
            return redirect()->route('web.rubros.index')
                ->with('error', 'No se puede desactivar: subrubro reservado del sistema.');
        }

        $subrubro->update(['activo' => !$subrubro->activo]);

        $estado = $subrubro->activo ? 'activado' : 'desactivado';
        return redirect()->route('web.rubros.index')->with('success', "Subrubro {$estado} correctamente.");
    }

    /**
     * T16: sin clasificación, lo que se registra en el subrubro no suma en Inicio ni en
     * Reportes. Se pide siempre; un aporte solo puede entrar y un retiro solo puede salir.
     */
    private function reglaClasificacion(Rubro $rubro): array
    {
        if (!RegistroReporteDisponible::existe()) return [];

        return ['clasificacion_resultado' => 'required|in:'.implode(',', ClasificacionSubrubros::permitidas($rubro->tipo))];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required'                  => 'El nombre es obligatorio.',
            'permitido_para.required'          => 'Elegí quién puede usar el subrubro.',
            'clasificacion_resultado.required' => 'Elegí si es plata del club o de los dueños.',
            'clasificacion_resultado.in'       => 'Esa opción no corresponde a este rubro.',
        ];
    }
}
