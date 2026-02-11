<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Deporte;
use App\Models\Grupo;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function show(int $id)
    {
        $alumno = Alumno::with(['deporte', 'grupo', 'planActivo'])->findOrFail($id);

        return view('alumnos.show', compact('alumno'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos = Grupo::where('activo', true)->orderBy('nombre')->get();

        return view('alumnos.create', compact('deportes', 'grupos'));
    }

    public function store(Request $request)
    {
        $rules = $this->validationRules($request);
        $rules['dni'] = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->where('deporte_id', $request->input('deporte_id'))];

        $validated = $request->validate($rules, $this->validationMessages());

        Alumno::create($validated);

        return redirect()->route('web.alumnos.index')->with('success', 'Alumno creado correctamente.');
    }

    public function edit(int $id)
    {
        $alumno = Alumno::findOrFail($id);
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos = Grupo::where('activo', true)->orderBy('nombre')->get();

        return view('alumnos.edit', compact('alumno', 'deportes', 'grupos'));
    }

    public function update(Request $request, int $id)
    {
        $alumno = Alumno::findOrFail($id);

        $rules = $this->validationRules($request);
        $rules['dni'] = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->ignore($alumno->id)->where('deporte_id', $request->input('deporte_id', $alumno->deporte_id))];
        $rules['activo'] = 'boolean';

        $validated = $request->validate($rules, $this->validationMessages());

        if (!$request->has('activo')) {
            $validated['activo'] = false;
        }

        $alumno->update($validated);

        return redirect()->route('web.alumnos.show', $alumno->id)->with('success', 'Alumno actualizado correctamente.');
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
            'celular' => 'required|string|max:255',
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
