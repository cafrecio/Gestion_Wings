<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use App\Models\Profesor;
use App\Rules\CbuOAlias;
use App\Rules\ImportePesos;
use App\Services\SubrubroSueldoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfesorWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Profesor::with('deporte')->withCount(['clases as clases_futuras_count' => fn ($q) => $q->where('cancelada', false)->noTerminadas()]);

        if ($request->filled('deporte_id')) {
            $query->where('deporte_id', $request->input('deporte_id'));
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('search')) {
            $search = addcslashes($request->input('search'), '%_\\');
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

        $liquidaciones = $profesor->liquidaciones()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->take(5)
            ->get();

        $clasesProximas = $profesor->clases()
            ->where('cancelada', false)
            ->whereDate('fecha', '>=', today())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->with(['grupo.deporte', 'grupo.nivel'])
            ->take(5)
            ->get();

        return view('profesores.show', compact('profesor', 'liquidaciones', 'clasesProximas'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('profesores.create', compact('deportes'));
    }

    public function store(Request $request)
    {
        ImportePesos::preparar($request, ['valor_hora']);
        $validated = $request->validate(
            $this->validationRules($request),
            $this->validationMessages()
        );

        $validated['activo'] = true;

        $validated['cbu_alias'] = CbuOAlias::normalizar($validated['cbu_alias'] ?? null);
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
        ImportePesos::preparar($request, ['valor_hora']);
        $profesor  = Profesor::findOrFail($id);
        $validated = $request->validate(
            $this->validationRules($request, $profesor->id),
            $this->validationMessages()
        );

        $validated['cbu_alias'] = CbuOAlias::normalizar($validated['cbu_alias'] ?? null);
        $profesor->update($validated);

        // Asegura el subrubro de sueldos y su vínculo por FK (D3). Cubre a
        // profesores viejos que quedaron sin subrubro_id tras la migración.
        $this->crearSubrubroProfesor($profesor);

        return redirect()->route('web.profesores.index')->with('success', 'Profesor actualizado correctamente.');
    }

    public function toggleActivo(Request $request, int $id)
    {
        $request->validate(['activo' => 'sometimes|boolean']);
        try {
            $profesor = app(\App\Services\ProfesorEstadoService::class)->cambiar($id, $request->user(), $request->has('activo') ? $request->boolean('activo') : null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $mensaje = collect($e->errors())->flatten()->first();
            if ($request->expectsJson()) return response()->json(['error' => $mensaje], 422);
            return back()->with('error', $mensaje);
        }

        if ($request->expectsJson()) {
            return response()->json(['activo' => (bool) $profesor->activo, 'mensaje' => $profesor->activo
                ? 'Profesora activa. Su usuario conserva el estado anterior; activalo en Usuarios si corresponde.'
                : 'Profesora inactiva. El usuario vinculado quedó desactivado y sus sesiones cerradas.']);
        }

        $estado = $profesor->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Profesor {$estado} correctamente.");
    }

    /**
     * Crea el subrubro de sueldo y lo vincula por FK (D3): la liquidación
     * lo resuelve por ese id, no reconstruyendo el nombre.
     *
     * Antes, si faltaba el rubro "Sueldos", esto hacía `return` y el
     * profesor quedaba guardado sin subrubro, con la pantalla diciendo
     * "creado correctamente". El servicio ahora lanza excepción.
     */
    private function crearSubrubroProfesor(Profesor $profesor): void
    {
        app(SubrubroSueldoService::class)->paraProfesor($profesor);
    }

    private function validationRules(Request $request, ?int $profesorId = null): array
    {
        $tipos = $this->tiposDeLiquidacion($request->input('deporte_id'));

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
            'cbu_alias'           => ['nullable', new CbuOAlias],
            'valor_hora'          => in_array(Deporte::TIPO_LIQUIDACION_HORA, $tipos, true)
                ? ['required', 'numeric', 'gt:0', new ImportePesos]
                : ['nullable', 'numeric', 'min:0', new ImportePesos],
            'porcentaje_comision' => in_array(Deporte::TIPO_LIQUIDACION_COMISION, $tipos, true)
                ? 'required|numeric|gt:0|max:100'
                : 'nullable|numeric|min:0|max:100',
        ];
    }

    /**
     * Cómo liquida el deporte hoy y desde el mes que viene: son las tarifas que
     * el formulario pide. Sin ellas, la liquidación se frena recién a fin de mes.
     */
    private function tiposDeLiquidacion(mixed $deporteId): array
    {
        $deporte = is_numeric($deporteId) ? Deporte::find((int) $deporteId) : null;
        if (!$deporte) {
            return [];
        }
        $siguiente = today()->startOfMonth()->addMonth();

        return [$deporte->tipo_liquidacion, $deporte->tipoLiquidacionParaPeriodo($siguiente->month, $siguiente->year)];
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
            'valor_hora.required'          => 'Cargá el valor por hora.',
            'valor_hora.gt'                => 'El valor por hora debe ser mayor a cero.',
            'valor_hora.numeric'           => 'El valor por hora debe ser un número.',
            'valor_hora.min'               => 'El valor por hora no puede ser negativo.',
            'porcentaje_comision.required' => 'Cargá el porcentaje de comisión.',
            'porcentaje_comision.gt'       => 'El porcentaje de comisión debe ser mayor a cero.',
            'porcentaje_comision.numeric'  => 'El porcentaje de comisión debe ser un número.',
            'porcentaje_comision.min'      => 'El porcentaje de comisión no puede ser negativo.',
            'porcentaje_comision.max'      => 'El porcentaje de comisión no puede superar 100.',
        ];
    }
}
