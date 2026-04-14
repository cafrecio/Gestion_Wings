<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AlumnoWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Alumno::with(['deporte', 'grupo']);

        if ($request->filled('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        if ($request->filled('grupo_id')) {
            $query->where('grupo_id', $request->input('grupo_id'));
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos = Grupo::where('activo', true)->orderBy('nombre')->get();

        return view('alumnos.index', compact('alumnos', 'deportes', 'grupos'));
    }

    public function autocomplete(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Alumno::with('grupo')
            ->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellido', 'like', "%{$q}%")
                      ->orWhere('dni', 'like', "%{$q}%");
            })
            ->orderBy('apellido')->orderBy('nombre')
            ->limit(8)
            ->get(['id', 'nombre', 'apellido', 'dni', 'grupo_id'])
            ->map(fn($a) => [
                'id'    => $a->id,
                'label' => $a->apellido . ', ' . $a->nombre,
                'sub'   => implode(' · ', array_filter([$a->dni, $a->grupo->nombre ?? null])),
                'url'   => route('web.alumnos.show', $a->id),
            ]);

        return response()->json($results);
    }

    public function show(int $id)
    {
        $alumno = Alumno::with(['deporte', 'grupo', 'planActivo.plan'])->findOrFail($id);

        return view('alumnos.show', compact('alumno'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos   = Grupo::with('planesActivos')->where('activo', true)->orderBy('nombre')->get();

        $grupoPlanesJson = $grupos->mapWithKeys(fn($g) => [
            $g->id => $g->planesActivos
                ->sortBy('clases_por_semana')
                ->values()
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'clases' => $p->clases_por_semana,
                    'precio' => (float) $p->precio_mensual,
                ]),
        ]);

        return view('alumnos.create', compact('deportes', 'grupos', 'grupoPlanesJson'));
    }

    public function store(Request $request)
    {
        $rules = $this->validationRules($request);
        $rules['dni']     = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->where('deporte_id', $request->input('deporte_id'))];
        $rules['plan_id'] = ['required', Rule::exists('grupo_planes', 'id')->where('grupo_id', $request->input('grupo_id'))];

        $validated = $request->validate($rules, array_merge($this->validationMessages(), [
            'plan_id.required' => 'Debe seleccionar la frecuencia semanal.',
            'plan_id.exists'   => 'La frecuencia seleccionada no corresponde al grupo.',
        ]));

        $alumno = Alumno::create(Arr::except($validated, ['plan_id']));

        AlumnoPlan::create([
            'alumno_id'   => $alumno->id,
            'plan_id'     => $validated['plan_id'],
            'fecha_desde' => today(),
            'activo'      => true,
        ]);

        return redirect()->route('web.alumnos.index')->with('success', 'Alumno creado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $alumno = Alumno::findOrFail($id);
        $alumno->update(['activo' => !$alumno->activo]);

        $estado = $alumno->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Alumno {$estado} correctamente.");
    }

    public function edit(int $id)
    {
        $alumno   = Alumno::with(['deporte', 'planActivo'])->findOrFail($id);
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos   = Grupo::with('planesActivos')->where('activo', true)->where('deporte_id', $alumno->deporte_id)->orderBy('nombre')->get();

        $grupoPlanesJson = $grupos->mapWithKeys(fn($g) => [
            $g->id => $g->planesActivos
                ->sortBy('clases_por_semana')
                ->values()
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'clases' => $p->clases_por_semana,
                    'precio' => (float) $p->precio_mensual,
                ]),
        ]);

        return view('alumnos.edit', compact('alumno', 'deportes', 'grupos', 'grupoPlanesJson'));
    }

    public function update(Request $request, int $id)
    {
        $alumno = Alumno::findOrFail($id);

        $rules = $this->validationRules($request);
        $rules['dni'] = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->ignore($alumno->id)->where('deporte_id', $request->input('deporte_id', $alumno->deporte_id))];
        $validated = $request->validate($rules, $this->validationMessages());

        $alumno->update(Arr::except($validated, ['plan_id']));

        // Si se envía un plan distinto al activo, crear nuevo AlumnoPlan
        if ($request->filled('plan_id')) {
            $planId = (int) $request->input('plan_id');
            if (!$alumno->planActivo || $alumno->planActivo->plan_id != $planId) {
                AlumnoPlan::create([
                    'alumno_id'   => $alumno->id,
                    'plan_id'     => $planId,
                    'fecha_desde' => today(),
                    'activo'      => true,
                ]);
            }
        }

        return redirect()->route('web.alumnos.index')->with('success', 'Alumno actualizado correctamente.');
    }

    private function validationRules(Request $request): array
    {
        $esMenor = false;
        if ($request->filled('fecha_nacimiento')) {
            try {
                $esMenor = Carbon::parse($request->input('fecha_nacimiento'))->diffInYears(Carbon::now()) < 18;
            } catch (\Exception $e) {
            }
        }

        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today',
            'celular' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'deporte_id' => 'required|exists:deportes,id',
            'grupo_id' => 'required|exists:grupos,id',
            'nombre_tutor' => $esMenor ? 'required|string|max:255' : 'nullable|string|max:255',
            'telefono_tutor' => $esMenor ? 'required|string|max:255' : 'nullable|string|max:255',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.unique' => 'Ya existe un alumno con ese DNI en el mismo deporte.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'celular.required' => 'El celular es obligatorio.',
            'deporte_id.required' => 'Debe seleccionar un deporte.',
            'deporte_id.exists' => 'El deporte seleccionado no existe.',
            'grupo_id.required' => 'Debe seleccionar un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'nombre_tutor.required' => 'El nombre del tutor es obligatorio para menores de edad.',
            'telefono_tutor.required' => 'El teléfono del tutor es obligatorio para menores de edad.',
            'email.email' => 'El email debe ser una dirección válida.',
        ];
    }
}
