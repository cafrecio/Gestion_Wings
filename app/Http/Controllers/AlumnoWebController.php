<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Services\CobranzaEstadoService;
use App\Services\PagoCuotaService;
use App\Support\FechaIngresoRegla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AlumnoWebController extends Controller
{
    public function cuotaAltaPreview(Request $request, PagoCuotaService $service)
    {
        $data = $request->validate([
            'fecha_alta' => [
                'bail',
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && !FechaIngresoRegla::esValida($value)) {
                        $fail(FechaIngresoRegla::mensajeError());
                    }
                },
            ],
            'plan_id' => 'nullable|integer|exists:grupo_planes,id',
        ], $this->validationMessages());
        return response()->json($service->previsualizarCuotaAlta($data['fecha_alta'], isset($data['plan_id']) ? (int) $data['plan_id'] : null));
    }

    public function inscripcionPreview(Request $request)
    {
        $data = $request->validate([
            'dni' => 'required|string|max:20',
            'fecha_alta' => [
                'bail',
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->filled('alumno_id')) {
                        $editado = Alumno::find($request->integer('alumno_id'));
                        if ($editado && $editado->fecha_alta) {
                            try {
                                if (Carbon::parse($value)->isSameDay($editado->fecha_alta)) {
                                    return;
                                }
                            } catch (\Throwable) {
                                return;
                            }
                        }
                    }
                    if ($value && !FechaIngresoRegla::esValida($value)) {
                        $fail(FechaIngresoRegla::mensajeError());
                    }
                },
            ],
        ], $this->validationMessages());
        $service = app(\App\Services\InscripcionService::class);
        $importe = $service->importe();
        $cargo = $service->cargo($data['dni']);
        $mensaje = $cargo
            ? 'Esta persona ya tiene una inscripción registrada. Saldo: ' . \App\Support\FormatoImporte::pesos($cargo->saldo_pendiente)
            : 'Corresponde inscripción por única vez: ' . \App\Support\FormatoImporte::pesos($importe);
        if ($request->filled('alumno_id')) {
            $editado = Alumno::findOrFail($request->integer('alumno_id'));
            if ($editado->fecha_alta->format('Y-m-d') !== Carbon::parse($data['fecha_alta'])->format('Y-m-d') && $cargo) {
                $mensaje = $cargo->monto_cobrado > 0 ? 'No se puede modificar el ingreso: la inscripción tiene pagos registrados.'
                    : 'Se conservará una única inscripción de ' . \App\Support\FormatoImporte::pesos($cargo->monto_original);
            }
            if (!$cargo) $mensaje = 'Editar el ingreso no genera inscripción retroactiva.';
        }
        return response()->json([
            'importe' => $importe,
            'mensaje' => $mensaje,
        ]);
    }
    public function index(Request $request, CobranzaEstadoService $cobranzaService)
    {
        $query = Alumno::with(['deporte', 'grupo.deporte', 'grupo.nivel']);

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
            $query->buscar($request->input('search'));
        }

        $alumnos = $query->orderBy('apellido')->orderBy('nombre')->paginate(12)->withQueryString();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos = Grupo::with(['deporte', 'nivel'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();

        $estadosCobranza = $cobranzaService->estadosParaAlumnos($alumnos->getCollection());

        return view('alumnos.index', compact('alumnos', 'deportes', 'grupos', 'estadosCobranza'));
    }

    public function autocomplete(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $results = Alumno::with(['grupo.deporte', 'grupo.nivel'])
            ->buscar($q)
            ->orderBy('apellido')->orderBy('nombre')
            ->limit(8)
            ->get(['id', 'nombre', 'apellido', 'dni', 'grupo_id'])
            ->map(fn($a) => [
                'id'    => $a->id,
                'label' => $a->apellido . ', ' . $a->nombre,
                'sub'   => implode(' · ', array_filter([$a->dni, $a->grupo->nombre_completo ?? null])),
                'url'   => route('web.alumnos.show', $a->id),
            ]);

        return response()->json($results);
    }

    public function show(int $id, CobranzaEstadoService $cobranzaService)
    {
        $alumno = Alumno::with(['deporte', 'grupo.deporte', 'grupo.nivel', 'planActivo.plan'])->findOrFail($id);

        $estadoCobranza = $cobranzaService->estadoAlumno($alumno->id);
        $mismoDni = $alumno->dni
            ? Alumno::where('activo', true)->where('dni', $alumno->dni)->get()->push($alumno)->unique('id')->values()
            : collect([$alumno]);
        $saldoCobranza = $cobranzaService->saldoDeAlumnos($mismoDni)[$alumno->id] ?? [
            'cuotas' => 0.0,
            'inscripcion' => 0.0,
            'punitorios' => 0.0,
            'total' => 0.0,
        ];
        $totalAdeudado = (float) ($saldoCobranza['total'] ?? 0.0);

        $fechaVencimiento = null;
        if ($estadoCobranza['estado'] === CobranzaEstadoService::ESTADO_EN_PLAZO) {
            foreach ($estadoCobranza['deudas_pendientes'] as $deudaPendiente) {
                $limiteCuota = $cobranzaService->limiteDeLaCuota($alumno, $deudaPendiente);
                if ($limiteCuota && ($fechaVencimiento === null || $limiteCuota->gt($fechaVencimiento))) {
                    $fechaVencimiento = $limiteCuota;
                }
            }
            if (($saldoCobranza['inscripcion'] ?? 0) > 0) {
                $limiteAlta = $cobranzaService->limiteDeAlta($alumno);
                if ($limiteAlta && ($fechaVencimiento === null || $limiteAlta->gt($fechaVencimiento))) {
                    $fechaVencimiento = $limiteAlta;
                }
            }
        }

        $estadoCobranza['total_adeudado'] = $totalAdeudado;
        $estadoCobranza['fecha_vencimiento'] = $fechaVencimiento;

        $ultimosPagos = $alumno->pagos()
            ->with(['deudasCuota', 'cargos'])
            ->orderByDesc('fecha_pago')
            ->limit(12)
            ->get();

        $asistenciasMes = $alumno->asistencias()
            ->with('clase')
            ->whereHas('clase', fn($q) => $q
                ->whereMonth('fecha', now()->month)
                ->whereYear('fecha', now()->year)
            )
            ->where('presente', true)
            ->get()
            ->sortBy(fn($as) => $as->clase?->fecha);

        $cuotasCondonadas = $alumno->deudaCuotas()->where('estado', 'CONDONADA')->orderBy('periodo')->get();
        $punitorios = \App\Models\CargoAlumno::where('alumno_id', $id)->where('tipo', 'PUNITORIO')
            ->where('estado', 'VIGENTE')->with(['deudaCuota', 'pagos'])->orderBy('deuda_cuota_id')->get();
        // T58: solo el dueño ve los meses pasados que puede crear.
        $mesesPasados = auth()->user()?->isAdmin() ? app(PagoCuotaService::class)->mesesPasadosSinCuota($alumno) : [];
        $puedeEliminar = auth()->user()?->isAdmin() && $alumno->esEliminable();
        $motivoNoEliminable = $alumno->motivoNoEliminable();
        return view('alumnos.show', compact('alumno', 'estadoCobranza', 'ultimosPagos', 'asistenciasMes', 'cuotasCondonadas', 'punitorios', 'mesesPasados', 'totalAdeudado', 'fechaVencimiento', 'puedeEliminar', 'motivoNoEliminable'));
    }

    public function condonarDeuda(Request $request, int $id, PagoCuotaService $pagoCuotaService)
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'El motivo de la condonación es obligatorio.',
            'motivo.string' => 'El motivo de la condonación debe ser un texto.',
            'motivo.min' => 'El motivo de la condonación debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo de la condonación no puede superar los 500 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        try {
            $deuda = $pagoCuotaService->condonarDeuda(
                $id,
                $validator->validated()['motivo'],
                (int) $request->user()->id
            );
        } catch (\Exception $exception) {
            return back()->with('error', \App\Support\AvisoError::texto($exception));
        }

        return redirect()
            ->route('web.alumnos.show', $deuda->alumno_id)
            ->with('success', 'Deuda condonada correctamente.');
    }

    /** T58: el dueño crea la cuota de un mes pasado que nunca se generó. */
    public function crearCuotaPasada(Request $request, int $id, PagoCuotaService $pagoCuotaService)
    {
        $periodo = (string) $request->input('periodo', '');

        try {
            $deuda = $pagoCuotaService->crearCuotaMesPasado($id, $periodo, (int) $request->user()->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $exception) {
            return back()->with('error', \App\Support\AvisoError::texto($exception));
        }

        $mes = Carbon::createFromFormat('Y-m-d', $deuda->periodo . '-01')->locale('es')->translatedFormat('F Y');
        return redirect()
            ->route('web.alumnos.show', $id)
            ->with('success', 'Se creó la cuota de ' . $mes . ': ' . \App\Support\FormatoImporte::pesos((float) $deuda->monto_original) . '.');
    }

    public function reactivarDeuda(Request $request, int $id, PagoCuotaService $service)
    {
        return $this->decidirPerdon($request, fn ($motivo, $usuario) => $service->reactivarDeuda($id, $motivo, $usuario), 'Cuota reactivada.');
    }

    public function condonarInscripcion(Request $request, int $id, \App\Services\InscripcionService $service)
    {
        return $this->decidirPerdon($request, fn ($motivo, $usuario) => $service->condonar($id, $motivo, $usuario), 'Inscripción condonada.');
    }

    public function reactivarInscripcion(Request $request, int $id, \App\Services\InscripcionService $service)
    {
        return $this->decidirPerdon($request, fn ($motivo, $usuario) => $service->reactivar($id, $motivo, $usuario), 'Inscripción reactivada.');
    }

    private function decidirPerdon(Request $request, callable $accion, string $mensaje)
    {
        $validator = Validator::make(['motivo' => is_string($request->input('motivo')) ? trim($request->input('motivo')) : $request->input('motivo')], [
            'motivo' => 'required|string|min:10|max:500',
        ], ['motivo.required' => 'El motivo es obligatorio.', 'motivo.min' => 'El motivo debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.']);
        if ($validator->fails()) return back()->with('error', $validator->errors()->first());
        try {
            $registro = $accion($validator->validated()['motivo'], (int) $request->user()->id);
        } catch (\Exception $exception) {
            return back()->with('error', \App\Support\AvisoError::texto($exception));
        }
        return redirect()->route('web.alumnos.show', $registro->alumno_id)->with('success', $mensaje);
    }

    public function condonarPunitorio(Request $request, int $id, \App\Services\PunitorioService $service)
    {
        return $this->decidirPerdon($request, fn ($motivo, $usuario) => $service->condonar($id, $motivo, $usuario), 'Recargo condonado.');
    }

    public function reactivarPunitorio(Request $request, int $id, \App\Services\PunitorioService $service)
    {
        return $this->decidirPerdon($request, fn ($motivo, $usuario) => $service->reactivar($id, $motivo, $usuario), 'Recargo reactivado.');
    }

    /**
     * Anular un cobro del dueño, que no pasó por ninguna caja (A13/B1).
     */
    public function anularPago(Request $request, int $id, PagoCuotaService $pagoCuotaService)
    {
        $request->validate([
            'motivo' => 'required|string|min:10|max:500',
        ], [
            'motivo.required' => 'El motivo de la anulación es obligatorio.',
            'motivo.min' => 'El motivo de la anulación debe tener al menos 10 caracteres.',
        ]);

        $pago = \App\Models\Pago::findOrFail($id);

        try {
            $pagoCuotaService->anularCobroAdmin($id, $request->input('motivo'), (int) $request->user()->id);
        } catch (\Exception $exception) {
            return back()->with('error', \App\Support\AvisoError::texto($exception));
        }

        return redirect()
            ->route('web.alumnos.show', $pago->alumno_id)
            ->with('success', 'Cobro anulado y deuda restituida.');
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos   = Grupo::with(['deporte', 'nivel', 'planesActivos'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();

        $grupoPlanesJson = $grupos->mapWithKeys(fn($g) => [
            $g->id => $g->planesActivos
                ->sortBy('clases_por_semana')
                ->values()
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'clases' => $p->clases_por_semana,
                    'precio' => $p->precioParaPeriodo(now()->format('Y-m'), today()),
                ]),
        ]);

        return view('alumnos.create', compact('deportes', 'grupos', 'grupoPlanesJson'));
    }

    public function store(Request $request)
    {
        $request->merge(['dni' => \App\Services\InscripcionService::dni((string) $request->input('dni'))]);
        $request->validate(['alta_token' => 'nullable|uuid']);
        $fechaIngreso = Carbon::parse($request->validate([
            'fecha_alta' => [
                'bail',
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && !FechaIngresoRegla::esValida($value)) {
                        $fail(FechaIngresoRegla::mensajeError());
                    }
                },
            ],
        ], $this->validationMessages())['fecha_alta']);
        $mesCerrado = $fechaIngreso->copy()->startOfMonth()->lt(now()->startOfMonth());
        $fingerprint = hash('sha256', json_encode($request->only(['dni', 'nombre', 'apellido', 'fecha_nacimiento', 'fecha_alta', 'celular', 'email', 'nombre_tutor', 'telefono_tutor', 'grupo_id', 'deporte_id', 'plan_id', 'generar_cuota_actual'])));
        if ($request->filled('alta_token')) {
            $anterior = Alumno::where('alta_token', $request->alta_token)->first();
            if ($anterior && $anterior->alta_fingerprint !== $fingerprint) {
                throw \Illuminate\Validation\ValidationException::withMessages(['dni' => 'Este formulario ya fue guardado con otros datos. Abra un alta nueva.']);
            }
        }
        if ($request->filled('alta_token') && Alumno::where('alta_token', $request->alta_token)->exists()) {
            return redirect()->route('web.alumnos.index')->with('success', 'El alumno ya fue creado.');
        }
        $rules = $this->validationRules($request);
        $rules['dni']     = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->where('deporte_id', $request->input('deporte_id'))];
        $rules['plan_id'] = ['required', Rule::exists('grupo_planes', 'id')->where('grupo_id', $request->input('grupo_id'))];
        $rules['generar_cuota_actual'] = [Rule::requiredIf($mesCerrado), 'nullable', 'boolean'];

        $validated = $request->validate($rules, array_merge($this->validationMessages(), [
            'plan_id.required' => 'Debe seleccionar la frecuencia semanal.',
            'plan_id.exists'   => 'La frecuencia seleccionada no corresponde al grupo.',
            'generar_cuota_actual.required' => 'Elegí si se genera la cuota de este mes antes de guardar.',
            'generar_cuota_actual.boolean' => 'Elegí Sí o No para la cuota de este mes.',
        ]));
        $validated = $this->completarCelular($validated);

        DB::transaction(function () use ($validated, $request, $fingerprint) {
            app(\App\Services\InscripcionService::class)->bloquear($validated['dni']);
            if ($request->filled('alta_token')) {
                $anterior = Alumno::where('alta_token', $request->alta_token)->lockForUpdate()->first();
                if ($anterior && $anterior->alta_fingerprint !== $fingerprint) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['dni' => 'Este formulario ya fue guardado con otros datos. Abra un alta nueva.']);
                }
                if ($anterior) return;
            }
            $cuotaService = app(PagoCuotaService::class);
            $vista = $cuotaService->previsualizarCuotaAlta($validated['fecha_alta'], (int) $validated['plan_id']);
            if (($request->filled('cuota_periodo_visto') && $request->input('cuota_periodo_visto') !== $vista['periodo'])
                || ($request->filled('cuota_importe_visto') && (!is_numeric($request->input('cuota_importe_visto')) || (float) $request->input('cuota_importe_visto') !== (float) $vista['importe']))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'generar_cuota_actual' => 'Cambió el mes o el importe de la cuota. Revisá el aviso antes de guardar.',
                ]);
            }
            $alumno = Alumno::create(Arr::except($validated, ['plan_id', 'generar_cuota_actual']));
            $alumno->forceFill(['alta_token' => $request->input('alta_token'), 'alta_fingerprint' => $fingerprint])->save();

            AlumnoPlan::create([
                'alumno_id'   => $alumno->id,
                'plan_id'     => $validated['plan_id'],
                'fecha_desde' => $alumno->fecha_alta,
                'activo'      => true,
            ]);
            app(\App\Services\InscripcionService::class)->sincronizar($alumno, $request->user()->id);
            $cargo = app(\App\Services\InscripcionService::class)->cargo($alumno->dni);
            if ($request->filled('inscripcion_importe_visto') && $cargo && $cargo->alumno_id === $alumno->id && (float) $request->input('inscripcion_importe_visto') !== (float) $cargo->monto_original) {
                throw \Illuminate\Validation\ValidationException::withMessages(['fecha_alta' => 'El importe de inscripción cambió. Revise el nuevo importe antes de guardar.']);
            }
            $generar = $vista['mes_cerrado'] ? (bool) $validated['generar_cuota_actual'] : null;
            $cuota = $cuotaService->crearCuotaAlta($alumno, (int) $validated['plan_id'], $generar);
            $alumno->forceFill(['alta_cuota' => [
                'modo' => $vista['mes_cerrado'] ? ($generar ? 'MES_ACTUAL' : 'SIN_CUOTA') : 'AUTOMATICA',
                'usuario_id' => $request->user()->id, 'fecha' => now()->toIso8601String(),
                'fecha_ingreso' => $alumno->fecha_alta->format('Y-m-d'),
                'periodo' => $cuota?->periodo, 'monto' => (float) ($cuota?->monto_original ?? 0),
                'porcentaje' => $cuota ? (float) $cuota->porcentaje_alta : null,
            ]])->save();
        });

        return redirect()->route('web.alumnos.index')->with('success', 'Alumno creado correctamente.');
    }

    public function toggleActivo(Request $request, int $id)
    {
        try {
            // T38: reactivar le genera la cuota del mes en curso con el porcentaje de
            // reingreso del día, salvo que ya la tenga. Todo o nada.
            [$alumno, $cuota] = DB::transaction(function () use ($id) {
                $alumno = Alumno::whereKey($id)->lockForUpdate()->firstOrFail();
                $alumno->update(['activo' => !$alumno->activo]);
                $cuota = null;
                if ($alumno->activo) {
                    $cuota = app(PagoCuotaService::class)->crearCuotaReingreso($alumno);
                    // Reactivarlo responde la pregunta de la cola: sigue siendo alumno.
                    \App\Models\AlumnoRevisionCobranza::reactivarAuto($alumno->id, now()->format('Y-m'));
                }
                return [$alumno, $cuota];
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => \App\Support\AvisoError::texto($e)], 422);
            }
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }

        $mensaje = 'Alumno ' . ($alumno->activo ? 'activado' : 'desactivado') . ' correctamente.';
        if ($cuota) {
            $mes = Carbon::createFromFormat('Y-m-d', $cuota->periodo . '-01')->locale('es')->translatedFormat('F');
            $porcentaje = (float) $cuota->porcentaje_alta;
            $mensaje .= ' Se generó la cuota de ' . $mes . ': ' . \App\Support\FormatoImporte::pesos((float) $cuota->monto_original)
                . ($porcentaje < 100 ? ' (' . rtrim(rtrim(number_format($porcentaje, 2, ',', ''), '0'), ',') . ' % por reingreso)' : '') . '.';
        }

        if ($request->expectsJson()) {
            return response()->json(['activo' => (bool) $alumno->activo, 'mensaje' => $mensaje]);
        }

        return back()->with('success', $mensaje);
    }

    public function edit(int $id)
    {
        $alumno   = Alumno::with(['deporte', 'planActivo.plan'])->findOrFail($id);
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $grupos   = Grupo::with(['deporte', 'nivel', 'planesActivos'])
            ->where('grupos.activo', true)
            ->where('grupos.deporte_id', $alumno->deporte_id)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();

        $grupoPlanesJson = $grupos->mapWithKeys(fn($g) => [
            $g->id => $g->planesActivos
                ->sortBy('clases_por_semana')
                ->values()
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'clases' => $p->clases_por_semana,
                    'precio' => $p->precioParaPeriodo(app(\App\Services\CambioPlanService::class)->periodoAplicacion($alumno, $p), $alumno->fecha_alta),
                ]),
        ]);

        return view('alumnos.edit', compact('alumno', 'deportes', 'grupos', 'grupoPlanesJson'));
    }

    public function update(Request $request, int $id)
    {
        $alumno = Alumno::with('planActivo.plan')->findOrFail($id);
        $tienePlanValido = $alumno->planActivo && $alumno->planActivo->plan;
        $cambiaGrupo = $request->filled('grupo_id') && (int) $request->input('grupo_id') !== (int) $alumno->grupo_id;

        $rules = $this->validationRules($request, $alumno);
        $rules['dni'] = ['required', 'string', 'max:20', Rule::unique('alumnos', 'dni')->ignore($alumno->id)->where('deporte_id', $request->input('deporte_id', $alumno->deporte_id))];

        // Plan obligatorio si el alumno no tiene uno vigente o si cambia de grupo
        if (!$tienePlanValido || $cambiaGrupo) {
            $rules['plan_id'] = ['required', Rule::exists('grupo_planes', 'id')->where('grupo_id', $request->input('grupo_id'))];
        } elseif ($request->filled('plan_id')) {
            $rules['plan_id'] = [Rule::exists('grupo_planes', 'id')->where('grupo_id', $alumno->grupo_id)];
        }

        $validated = $request->validate($rules, array_merge($this->validationMessages(), [
            'plan_id.required' => $cambiaGrupo ? 'Debe seleccionar la frecuencia semanal del nuevo grupo.' : 'Debe seleccionar la frecuencia semanal.',
            'plan_id.exists'   => 'La frecuencia seleccionada no corresponde al grupo.',
        ]));
        $validated = $this->completarCelular($validated);

        DB::transaction(function () use ($request, $validated, $alumno) {
        $inscripcion = app(\App\Services\InscripcionService::class);
        $inscripcion->bloquear($alumno->dni);
        $alumno = Alumno::whereKey($alumno->id)->lockForUpdate()->firstOrFail();
        $fechaAnterior = $alumno->fecha_alta->format('Y-m-d');
        if ($inscripcion->cargo($alumno->dni) && $inscripcion::dni($validated['dni']) !== $inscripcion::dni($alumno->dni)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['dni' => 'El DNI está vinculado a una inscripción. Su corrección requiere revisar ese cargo.']);
        }
        if ($fechaAnterior !== Carbon::parse($validated['fecha_alta'])->format('Y-m-d')) {
            if (!FechaIngresoRegla::esValida($validated['fecha_alta'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha_alta' => FechaIngresoRegla::mensajeError(),
                ]);
            }
            $request->validate(['motivo_fecha_alta' => 'required|string|min:5|max:500']);
            $alumno->fill(Arr::except($validated, ['plan_id']));
            $inscripcion->sincronizar($alumno, $request->user()->id, $fechaAnterior, $request->input('motivo_fecha_alta'));
        }
        $alumno->update(Arr::except($validated, ['plan_id']));

        // Si se envía un plan distinto al activo, crear nuevo AlumnoPlan
        if ($request->filled('plan_id')) {
            $planId = (int) $request->input('plan_id');
            if ((!$alumno->planActivo || $alumno->planActivo->plan_id != $planId)
                && $alumno->planProgramado?->plan_id != $planId) {
                app(\App\Services\CambioPlanService::class)->cambiar($alumno, \App\Models\GrupoPlan::findOrFail($planId));
            }
        }

        });
        return redirect()->route('web.alumnos.index')->with('success', 'Alumno actualizado correctamente.');
    }

    private function validationRules(Request $request, ?Alumno $alumno = null): array
    {
        $esMenor = false;
        if ($request->filled('fecha_nacimiento')) {
            try {
                $esMenor = Carbon::parse($request->input('fecha_nacimiento'))->diffInYears(Carbon::now()) < 18;
            } catch (\Exception $e) {
            }
        }

        $cambiaFechaAlta = true;
        if ($alumno && $alumno->fecha_alta) {
            $fechaNueva = $request->input('fecha_alta');
            if ($fechaNueva) {
                try {
                    $cambiaFechaAlta = Carbon::parse($fechaNueva)->format('Y-m-d') !== $alumno->fecha_alta->format('Y-m-d');
                } catch (\Throwable) {
                    $cambiaFechaAlta = true;
                }
            }
        }

        $fechaAltaRules = ['bail', 'required', 'date'];
        if ($cambiaFechaAlta) {
            $fechaAltaRules[] = function ($attribute, $value, $fail) {
                if ($value && !FechaIngresoRegla::esValida($value)) {
                    $fail(FechaIngresoRegla::mensajeError());
                }
            };
        }

        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today',
            // Obligatoria: la columna es NOT NULL y el boot() del modelo solo
            // completa la fecha al crear, no al actualizar.
            'fecha_alta' => $fechaAltaRules,
            // Obligatorio para mayores: la columna es NOT NULL, asi que dejarlo vacio no
            // daba un mensaje de validacion sino un error 500 contra la base.
            // Un menor puede no tener celular propio (A26): se acepta vacio y se guarda
            // el telefono del tutor, que para un menor es obligatorio. Ver completarCelular().
            'celular' => $esMenor ? 'nullable|string|max:255' : 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'deporte_id' => 'required|exists:deportes,id',
            // El grupo tiene que ser del deporte elegido. Sin la condicion, el
            // formulario aceptaba un alumno de Patin en un grupo de Futbol: paso de
            // verdad el 06/09/2026 y quedo el alumno 17 en la base con deporte Patin
            // y grupo 8, que es de Futbol.
            //
            // No es un detalle de prolijidad. El deporte manda en asistencias,
            // liquidaciones y cobranza, y el grupo manda en las clases: un alumno asi
            // aparece en las listas de un deporte al que no pertenece, y su cuota se
            // cuenta donde no va.
            //
            // El plan ya estaba atado al grupo (mas arriba, en store y en update). El
            // grupo era el eslabon que habia quedado suelto.
            'grupo_id' => [
                'required',
                Rule::exists('grupos', 'id')->where('deporte_id', $request->input('deporte_id')),
            ],
            'nombre_tutor' => $esMenor ? 'required|string|max:255' : 'nullable|string|max:255',
            'telefono_tutor' => $esMenor ? 'required|string|max:255' : 'nullable|string|max:255',
        ];
    }

    /**
     * La columna celular es NOT NULL. Cuando un menor no tiene celular propio vale el
     * del tutor: es el telefono al que el club llama de todos modos.
     */
    private function completarCelular(array $validated): array
    {
        if (blank($validated['celular'] ?? null)) {
            $validated['celular'] = $validated['telefono_tutor'];
        }

        return $validated;
    }

    /**
     * T65 (DU-48, ALU-024): El dueño elimina un alumno cargado por error sin cobros ni asistencias.
     */
    public function destroy(Request $request, int $id)
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403, 'Solo el dueño puede eliminar alumnos.');
        }

        $alumno = Alumno::findOrFail($id);

        return DB::transaction(function () use ($alumno, $request) {
            $alumnoBloqueado = Alumno::where('id', $alumno->id)->lockForUpdate()->first();
            if (!$alumnoBloqueado) {
                return redirect()->route('web.alumnos.index')->with('error', 'El alumno no fue encontrado.');
            }

            if (!$alumnoBloqueado->esEliminable()) {
                $motivo = $alumnoBloqueado->motivoNoEliminable();
                $mensaje = "No se puede eliminar el alumno: {$motivo} Puede darlo de baja cambiando su estado a inactivo.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $mensaje], 422);
                }
                return redirect()->route('web.alumnos.show', $alumnoBloqueado->id)->with('error', $mensaje);
            }

            // 1. Revisiones de cobranza
            DB::table('alumnos_revision_cobranza')->where('alumno_id', $alumnoBloqueado->id)->delete();

            // 2. Planes del alumno
            DB::table('alumno_planes')->where('alumno_id', $alumnoBloqueado->id)->delete();

            // 3. Cargos del alumno (tratar antes de las cuotas por la FK RESTRICT de deuda_cuota_id)
            $dniNorm = \App\Services\InscripcionService::dni($alumnoBloqueado->dni);
            $otroAlumnoMismoDni = Alumno::where('id', '!=', $alumnoBloqueado->id)
                ->where('dni', $alumnoBloqueado->dni)
                ->first();

            $cargos = DB::table('cargos_alumno')->where('alumno_id', $alumnoBloqueado->id)->get();
            foreach ($cargos as $cargo) {
                if ($cargo->tipo === 'INSCRIPCION' && $otroAlumnoMismoDni) {
                    // Solo se reasigna la inscripción compartida al otro alumno del mismo DNI
                    DB::table('cargos_alumno')
                        ->where('id', $cargo->id)
                        ->update(['alumno_id' => $otroAlumnoMismoDni->id]);
                } else {
                    // Cargos propios (incluidos recargos/punitorios ligados a cuota) o inscripción exclusiva:
                    // se eliminan sus eventos y luego el cargo
                    DB::table('cargo_alumno_eventos')->where('cargo_alumno_id', $cargo->id)->delete();
                    DB::table('cargos_alumno')->where('id', $cargo->id)->delete();
                }
            }

            if (!$otroAlumnoMismoDni) {
                $hayMasCargos = DB::table('cargos_alumno')->where('dni', $dniNorm)->exists();
                $hayMasAlumnos = DB::table('alumnos')->where('id', '!=', $alumnoBloqueado->id)->where('dni', $alumnoBloqueado->dni)->exists();
                if (!$hayMasCargos && !$hayMasAlumnos) {
                    DB::table('inscripcion_personas')->where('dni', $dniNorm)->delete();
                }
            }

            // 4. Deuda cuotas impagas: eliminar mediante Eloquent para que HistorialAnaliticoReportesObserver
            // registre la baja en el historial analítico y Sueldos no siga contando la cuota borrada (H2)
            foreach ($alumnoBloqueado->deudaCuotas()->get() as $cuota) {
                $cuota->delete();
            }

            // 5. Eliminar alumno
            $alumnoBloqueado->delete();

            $mensaje = 'Alumno eliminado correctamente.';
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $mensaje]);
            }
            return redirect()->route('web.alumnos.index')->with('success', $mensaje);
        });
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
            'fecha_alta.required' => 'La fecha de alta es obligatoria.',
            'fecha_alta.date' => 'La fecha de alta no es una fecha válida.',
            'celular.required' => 'El celular es obligatorio.',
            'deporte_id.required' => 'Debe seleccionar un deporte.',
            'deporte_id.exists' => 'El deporte seleccionado no existe.',
            'grupo_id.required' => 'Debe seleccionar un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no pertenece al deporte elegido.',
            'nombre_tutor.required' => 'El nombre del tutor es obligatorio para menores de edad.',
            'telefono_tutor.required' => 'El teléfono del tutor es obligatorio para menores de edad.',
            'email.email' => 'El email debe ser una dirección válida.',
        ];
    }
}
