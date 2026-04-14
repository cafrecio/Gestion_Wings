<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfesorWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Profesor::with('deporte');

        if ($request->filled('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
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

        $profesores = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();
        $deportes   = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('profesores.index', compact('profesores', 'deportes'));
    }

    public function show(int $id)
    {
        $profesor = Profesor::with('deporte')->findOrFail($id);

        return view('profesores.show', compact('profesor'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('profesores.create', compact('deportes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        $validated['activo'] = true;

        $profesor = Profesor::create($validated);

        $this->crearSubrubroProfesor($profesor);

        return redirect()->route('web.profesores.index')->with('success', 'Profesor creado correctamente.');
    }

    public function edit(int $id)
    {
        $profesor = Profesor::with('deporte')->findOrFail($id);
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('profesores.edit', compact('profesor', 'deportes'));
    }

    public function update(Request $request, int $id)
    {
        $profesor  = Profesor::findOrFail($id);
        $validated = $request->validate(
            $this->validationRules($profesor->id),
            $this->validationMessages()
        );

        $profesor->update($validated);

        return redirect()->route('web.profesores.index')->with('success', 'Profesor actualizado correctamente.');
    }

    public function toggleActivo(int $id)
    {
        $profesor = Profesor::findOrFail($id);
        $profesor->update(['activo' => !$profesor->activo]);

        $estado = $profesor->activo ? 'activado' : 'desactivado';

        return back()->with('success', "Profesor {$estado} correctamente.");
    }

    private function crearSubrubroProfesor(Profesor $profesor): void
    {
        $profesor->loadMissing('deporte');

        $nombreSubrubro = ($profesor->deporte->nombre ?? 'Sin deporte')
            . '-' . $profesor->nombre . ' ' . $profesor->apellido;

        $rubroSueldos = Rubro::where('nombre', 'Sueldos')->first();

        if (! $rubroSueldos) {
            return;
        }

        Subrubro::firstOrCreate(
            ['nombre' => $nombreSubrubro],
            [
                'rubro_id'             => $rubroSueldos->id,
                'permitido_para'       => 'ADMIN',
                'afecta_caja'          => false,
                'es_reservado_sistema' => true,
            ]
        );
    }

    private function validationRules(?int $profesorId = null): array
    {
        return [
            'nombre'              => 'required|string|max:255',
            'apellido'            => 'required|string|max:255',
            'deporte_id'          => 'required|exists:deportes,id',
            'dni'                 => ['required', 'string', 'max:20', Rule::unique('profesores', 'dni')->ignore($profesorId)],
            'fecha_nacimiento'    => 'required|date|before:today',
            'direccion'           => 'required|string|max:255',
            'localidad'           => 'required|string|max:255',
            'email'               => 'nullable|email|max:255',
            'telefono'            => 'required|string|max:50',
            'valor_hora'          => 'nullable|numeric|min:0',
            'porcentaje_comision' => 'nullable|numeric|min:0|max:100',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'nombre.required'              => 'El nombre es obligatorio.',
            'apellido.required'            => 'El apellido es obligatorio.',
            'deporte_id.required'          => 'Debe seleccionar un deporte.',
            'deporte_id.exists'            => 'El deporte seleccionado no existe.',
            'dni.required'                 => 'El DNI es obligatorio.',
            'dni.unique'                   => 'Ya existe un profesor con ese DNI.',
            'fecha_nacimiento.required'    => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before'      => 'La fecha de nacimiento debe ser anterior a hoy.',
            'direccion.required'           => 'La dirección es obligatoria.',
            'localidad.required'           => 'La localidad es obligatoria.',
            'telefono.required'            => 'El teléfono es obligatorio.',
            'email.email'                  => 'El email debe ser una dirección válida.',
            'valor_hora.numeric'           => 'El valor por hora debe ser un número.',
            'valor_hora.min'               => 'El valor por hora no puede ser negativo.',
            'porcentaje_comision.numeric'  => 'El porcentaje de comisión debe ser un número.',
            'porcentaje_comision.min'      => 'El porcentaje de comisión no puede ser negativo.',
            'porcentaje_comision.max'      => 'El porcentaje de comisión no puede superar 100.',
        ];
    }
}
