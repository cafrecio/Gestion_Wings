<?php

namespace App\Http\Controllers;

use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use Illuminate\Http\Request;

class GrupoWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Grupo::with(['deporte', 'planesActivos']);

        if ($request->filled('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        $grupos   = $query->orderBy('nombre')->paginate(12)->withQueryString();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('grupos.index', compact('grupos', 'deportes'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('grupos.create', compact('deportes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'                         => 'required|string|max:255',
            'deporte_id'                     => 'required|exists:deportes,id',
            'planes'                         => 'nullable|array',
            'planes.*.clases_por_semana'     => 'required|integer|min:1|max:7',
            'planes.*.precio_mensual'        => 'required|numeric|min:0',
        ], [
            'nombre.required'                        => 'El nombre es obligatorio.',
            'deporte_id.required'                    => 'Debe seleccionar un deporte.',
            'deporte_id.exists'                      => 'El deporte seleccionado no existe.',
            'planes.*.clases_por_semana.required'    => 'Indicá la frecuencia.',
            'planes.*.precio_mensual.required'       => 'Ingresá el precio.',
            'planes.*.precio_mensual.numeric'        => 'El precio debe ser un número.',
        ]);

        $grupo = Grupo::create([
            'nombre'     => $validated['nombre'],
            'deporte_id' => $validated['deporte_id'],
            'activo'     => true,
        ]);

        foreach ($validated['planes'] ?? [] as $planData) {
            GrupoPlan::create([
                'grupo_id'          => $grupo->id,
                'clases_por_semana' => $planData['clases_por_semana'],
                'precio_mensual'    => $planData['precio_mensual'],
                'activo'            => true,
            ]);
        }

        return redirect()->route('web.grupos.index')
            ->with('success', 'Grupo creado correctamente.');
    }

    public function show(int $id)
    {
        $grupo = Grupo::with([
            'deporte',
            'planes' => fn($q) => $q->orderBy('clases_por_semana'),
            'alumnos',
        ])->findOrFail($id);

        return view('grupos.show', compact('grupo'));
    }

    public function edit(int $id)
    {
        $grupo = Grupo::with([
            'deporte',
            'planes' => fn($q) => $q->orderBy('clases_por_semana'),
        ])->findOrFail($id);

        return view('grupos.edit', compact('grupo'));
    }

    public function update(Request $request, int $id)
    {
        $grupo = Grupo::findOrFail($id);

        $validated = $request->validate([
            'nombre'                         => 'required|string|max:255',
            'planes'                         => 'nullable|array',
            'planes.*.id'                    => 'nullable|integer',
            'planes.*.clases_por_semana'     => 'required|integer|min:1|max:7',
            'planes.*.precio_mensual'        => 'required|numeric|min:0',
        ], [
            'nombre.required'                        => 'El nombre es obligatorio.',
            'planes.*.clases_por_semana.required'    => 'Indicá la frecuencia.',
            'planes.*.precio_mensual.required'       => 'Ingresá el precio.',
            'planes.*.precio_mensual.numeric'        => 'El precio debe ser un número.',
        ]);

        $grupo->update(['nombre' => $validated['nombre']]);

        $planes = collect($validated['planes'] ?? []);
        $idsSubmitted = $planes->pluck('id')->filter()->map(fn($v) => (int) $v)->all();

        // Eliminar planes removidos (solo si no tienen alumnos activos)
        GrupoPlan::where('grupo_id', $id)
            ->when(!empty($idsSubmitted), fn($q) => $q->whereNotIn('id', $idsSubmitted))
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('alumno_planes')
                    ->whereColumn('alumno_planes.plan_id', 'grupo_planes.id')
                    ->where('alumno_planes.activo', true);
            })
            ->delete();

        foreach ($planes as $planData) {
            $planId = !empty($planData['id']) ? (int) $planData['id'] : null;

            if ($planId) {
                GrupoPlan::where('id', $planId)->where('grupo_id', $id)->update([
                    'clases_por_semana' => $planData['clases_por_semana'],
                    'precio_mensual'    => $planData['precio_mensual'],
                ]);
            } else {
                // Evitar duplicar frecuencia
                $existe = GrupoPlan::where('grupo_id', $id)
                    ->where('clases_por_semana', $planData['clases_por_semana'])
                    ->exists();

                if (! $existe) {
                    GrupoPlan::create([
                        'grupo_id'          => $id,
                        'clases_por_semana' => $planData['clases_por_semana'],
                        'precio_mensual'    => $planData['precio_mensual'],
                        'activo'            => true,
                    ]);
                }
            }
        }

        return redirect()->route('web.grupos.index')
            ->with('success', 'Grupo actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $grupo = Grupo::findOrFail($id);
        $grupo->update(['activo' => !$grupo->activo]);

        $estado = $grupo->activo ? 'activado' : 'desactivado';

        return back()->with('success', "Grupo {$estado} correctamente.");
    }

    public function storePlan(Request $request, int $id)
    {
        $grupo = Grupo::findOrFail($id);

        $validated = $request->validate([
            'clases_por_semana' => 'required|integer|min:1|max:7',
            'precio_mensual'    => 'required|numeric|min:0',
        ]);

        $yaExiste = GrupoPlan::where('grupo_id', $id)
            ->where('clases_por_semana', $validated['clases_por_semana'])
            ->where('activo', true)
            ->exists();

        if ($yaExiste) {
            return back()->withErrors([
                'clases_por_semana' => 'Ya existe un plan con esa frecuencia para este grupo.',
            ])->withInput();
        }

        GrupoPlan::create([
            'grupo_id'          => $id,
            'clases_por_semana' => $validated['clases_por_semana'],
            'precio_mensual'    => $validated['precio_mensual'],
            'activo'            => true,
        ]);

        return redirect()->route('web.grupos.show', $id)->with('success', 'Plan agregado.');
    }

    public function destroyPlan(int $planId)
    {
        $plan = GrupoPlan::findOrFail($planId);

        $enUso = AlumnoPlan::where('plan_id', $planId)->where('activo', true)->exists();

        if ($enUso) {
            return back()->with('error', 'No se puede eliminar: hay alumnos con este plan activo.');
        }

        $grupoId = $plan->grupo_id;
        $plan->delete();

        return redirect()->route('web.grupos.show', $grupoId)->with('success', 'Plan eliminado.');
    }
}
