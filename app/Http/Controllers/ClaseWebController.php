<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\AsistenciaExceso;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Profesor;
use App\Services\ClaseService;
use App\Services\ProgramacionClasesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClaseWebController extends Controller
{
    public function __construct(private ClaseService $claseService, private ProgramacionClasesService $programacionClases) {}

    public function index(Request $request)
    {
        $ahora      = Carbon::now();
        $hoy        = $ahora->format('Y-m-d');
        $esAdmin    = Auth::user()->isAdmin();
        $esProfesor = Auth::user()->isProfesor();

        // 1. Clases de HOY — siempre todas, sin filtro (activas primero, canceladas al final)
        $clasesHoy = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores', 'asistencias'])
            ->whereDate('fecha', $hoy)
            ->orderBy('cancelada')
            ->orderBy('hora_inicio')
            ->get();

        // UP2.0: el profesor entraba y veía TODAS las clases de hoy de TODOS
        // los profesores mezcladas, y tenía que buscar la suya. Se separan
        // en "tus clases" (prioridad, arriba) y "otras clases" (contexto,
        // colapsado) sin sacarle visibilidad a nadie — solo se reordena.
        $miProfesorId    = $esProfesor ? Auth::user()->profesor_id : null;
        $misClasesHoy    = $clasesHoy;
        $otrasClasesHoy  = collect();
        if ($esProfesor && $miProfesorId) {
            $misClasesHoy   = $clasesHoy->filter(fn($c) => $c->profesores->contains('id', $miProfesorId))->values();
            $otrasClasesHoy = $clasesHoy->reject(fn($c) => $c->profesores->contains('id', $miProfesorId))->values();
        }

        // 2. Clases que NO son hoy — con filtros del request
        $hayFiltros = $request->anyFilled(['fecha', 'estado', 'deporte_id', 'grupo_id', 'profesor_id']);

        // Orden por defecto: si se están mirando clases pasadas, lo más
        // reciente primero; si son futuras, lo más próximo primero. El usuario
        // puede forzar otro orden desde el selector.
        $esPasado = $request->filled('estado') &&
                    in_array($request->input('estado'), ['finalizada', 'cerrada']);
        $dir = $esPasado ? 'desc' : 'asc';

        $query = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores', 'asistencias']);

        // El listado general excluye las clases de hoy para no duplicar lo que
        // ya se muestra arriba, EXCEPTO cuando se filtra por "cancelada" o se pide
        // explícitamente la fecha de hoy.
        $filtraCancelada = $request->input('estado') === 'cancelada';
        $filtraFechaHoy  = $request->filled('fecha') && $request->input('fecha') === $hoy;

        if (!$filtraCancelada && !$filtraFechaHoy) {
            $query->whereDate('fecha', '!=', $hoy);
        }

        match ($request->input('orden')) {
            'fecha_desc' => $query->orderBy('fecha', 'desc')->orderBy('hora_inicio', 'desc'),
            'fecha_asc'  => $query->orderBy('fecha', 'asc')->orderBy('hora_inicio', 'asc'),
            // Por grupo: se ordena por deporte y nivel, que es lo que compone
            // el nombre visible del grupo (no existe columna 'nombre').
            'grupo'      => $query
                ->join('grupos', 'clases.grupo_id', '=', 'grupos.id')
                ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
                ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
                ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
                ->orderBy('clases.fecha', $dir)
                ->select('clases.*'),
            default      => $query->orderBy('fecha', $dir)->orderBy('hora_inicio', $dir),
        };

        // Límite temporal según rol (solo hacia atrás, si el usuario filtra fechas pasadas).
        // Excepción: el filtro "finalizada" es el atajo de "sin asistencia" del
        // menú, y esas clases traban el pago del profesor sin importar qué tan
        // viejas sean. Ahí se ve todo el historial, o el número del menú no
        // coincidiría y encima no se podría cargar una lista vieja.
        $atajoSinAsistencia = $request->input('estado') === 'finalizada';
        if (!$esAdmin && !$atajoSinAsistencia) {
            $limiteAtras = Carbon::now()->subDays(35)->format('Y-m-d');
            $query->whereDate('fecha', '>=', $limiteAtras);
        }

        if (!$hayFiltros) {
            $query->where('cancelada', false)
                  ->whereDate('fecha', '>', today());
        } else {
            if ($request->filled('fecha')) {
                $query->whereDate('fecha', $request->input('fecha'));
            }
            if ($request->filled('deporte_id')) {
                $query->whereHas('grupo', fn($q) =>
                    $q->where('deporte_id', $request->input('deporte_id'))
                );
            }
            if ($request->filled('grupo_id')) {
                $query->where('grupo_id', $request->input('grupo_id'));
            }
            if ($request->filled('profesor_id')) {
                $query->whereHas('profesores', fn($q) =>
                    $q->where('profesores.id', $request->input('profesor_id'))
                );
            }
            if ($request->filled('estado')) {
                match ($request->input('estado')) {
                    'cancelada'  => $query->where('cancelada', true),
                    'programada' => $query
                        ->where('cancelada', false)
                        ->whereDate('fecha', '>', today()),
                    // Sin resolver: no se puede liquidar hasta que se cargue la
                    // lista. T34: solo clases a las que nunca se les tomó lista.
                    'finalizada' => $query
                        ->where('cancelada', false)
                        ->whereDate('fecha', '<', today())
                        ->where('validada_para_liquidacion', false)
                        ->doesntHave('asistencias'),
                    // T34: Lista tomada pero nadie presente. Solo visible para el dueño.
                    'sin_presentes' => $esAdmin ? $query
                        ->where('cancelada', false)
                        ->whereDate('fecha', '<', today())
                        ->where('validada_para_liquidacion', false)
                        ->whereHas('asistencias')
                        ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true)) : null,
                    // Resuelta para pago: tiene presentes o fue validada a mano.
                    'cerrada'    => $query
                        ->where('cancelada', false)
                        ->whereDate('fecha', '<', today())
                        ->where(fn($q) => $q
                            ->where('validada_para_liquidacion', true)
                            ->orWhereHas('asistencias', fn($a) => $a->where('presente', true))),
                    default      => null,
                };
            }
        }

        $clasesFiltradas = $query->paginate(20)->withQueryString();

        $grupos = Grupo::with(['deporte', 'nivel'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')->get();
        $deportes   = Deporte::where('activo', true)->orderBy('nombre')->get();
        $profesores = Profesor::where('activo', true)->orderBy('apellido')->get();

        return view('clases.index', compact(
            'clasesHoy', 'misClasesHoy', 'otrasClasesHoy', 'clasesFiltradas',
            'grupos', 'deportes', 'profesores',
            'ahora', 'esAdmin', 'esProfesor'
        ));
    }

    public function create()
    {
        $grupos    = Grupo::with(['deporte', 'nivel', 'planesActivos'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();
        $profesores = Profesor::where('activo', true)->orderBy('apellido')->get();

        return view('clases.create', compact('grupos', 'profesores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grupo_id'    => 'required|exists:grupos,id',
            'profesores'   => 'required|array|min:1',
            'profesores.*' => 'exists:profesores,id',
        ], [
            'grupo_id.required'    => 'Debe seleccionar un grupo.',
            'grupo_id.exists'      => 'El grupo seleccionado no existe.',
            'profesores.required'  => 'Seleccioná al menos una profesora activa del deporte de la clase.',
        ]);
        $programacion = $this->programacionClases->validar($request->all());
        $profesoresIds = $this->validarProfesoresDelDeporte($request, (int) Grupo::findOrFail($validated['grupo_id'])->deporte_id);
        $aviso = $this->programacionClases->aviso($programacion, (int) $validated['grupo_id'], $profesoresIds, (int) $request->user()->id);
        if ($this->programacionClases->requiereConfirmacion($aviso, $request->input('confirmar_cancha'))) {
            return redirect()->route('web.clases.create')
                ->withInput($request->except(['_token', 'confirmar_cancha']))->with('aviso_cancha', $aviso);
        }

        try {
            $count = DB::transaction(function () use ($programacion, $validated, $profesoresIds) {
                $creadas = 0;
                $serieId = $programacion['tipo_creacion'] === 'recurrente' ? Str::uuid()->toString() : null;
                foreach ($this->programacionClases->clases($programacion) as $horario) {
                    $clase = Clase::create([
                        'serie_id'    => $serieId,
                        'grupo_id'    => $validated['grupo_id'],
                        'fecha'       => $horario['fecha'],
                        'hora_inicio' => $horario['hora_inicio'],
                        'hora_fin'    => $horario['hora_fin'],
                        'cancelada'   => false,
                        'validada_para_liquidacion' => false,
                    ]);
                    $this->asignarProfesoresValidando($clase, $profesoresIds);
                    $creadas++;
                }

                return $creadas;
            });
        } catch (\Exception $e) {
            return back()->withInput($request->except('confirmar_cancha'))->with('error', \App\Support\AvisoError::texto($e));
        }

        return redirect()->route('web.clases.index')
            ->with('success', "{$count} clase(s) creada(s).");
    }

    /**
     * Asignar profesores a una clase validando solapamiento de horario.
     * Lanza excepción (sin asignar nada) si algún profesor ya tiene otra
     * clase que se solapa en fecha+horario.
     */
    private function asignarProfesoresValidando(Clase $clase, array $profesoresIds): void
    {
        foreach ($profesoresIds as $profesorId) {
            $chequeo = $this->claseService->verificarDisponibilidadProfesor($clase->id, (int) $profesorId);
            if (!$chequeo['puede_asignar']) {
                throw new \Exception($chequeo['razon']);
            }
        }

        $clase->profesores()->sync($profesoresIds);
    }

    /** A5: la selección de pantalla y los formularios manipulados deben respetar el deporte. */
    private function validarProfesoresDelDeporte(Request $request, int $deporteId): array
    {
        $datos = $request->validate([
            'profesores' => 'required|array|min:1',
            'profesores.*' => 'integer|exists:profesores,id',
        ], [
            'profesores.required' => 'Seleccioná al menos una profesora activa del deporte de la clase.',
            'profesores.min' => 'Seleccioná al menos una profesora activa del deporte de la clase.',
            'profesores.array' => 'Seleccioná las profesoras de la clase.',
        ]);
        $ids = array_values(array_unique(array_map('intval', $datos['profesores'] ?? [])));
        if ($ids && Profesor::whereIn('id', $ids)->where('deporte_id', $deporteId)->where('activo', true)->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'profesores' => 'Solo se pueden asignar profesores activos del deporte de la clase.',
            ]);
        }
        return $ids;
    }

    /** T66: la reasignación no altera clases canceladas ni cálculos ya cerrados. */
    private function validarCambioProfesores(Clase $clase): void
    {
        if ($clase->cancelada) {
            throw ValidationException::withMessages(['profesores' => 'La clase está cancelada. No se pueden cambiar sus profesoras.']);
        }

        // Leer bajo bloqueo coordina con cerrar/cancelar/pagar la liquidación.
        $actuales = $clase->profesores()->pluck('profesores.id');
        $liquidaciones = Liquidacion::where(function ($query) use ($clase, $actuales) {
            $query->whereHas('detalles', fn ($q) => $q
                ->where('tipo_referencia', LiquidacionDetalle::TIPO_CLASE)
                ->where('referencia_id', $clase->id))
                ->orWhere(fn ($q) => $q->where('tipo', Liquidacion::TIPO_COMISION)
                    ->whereIn('profesor_id', $actuales)
                    ->where('mes', $clase->fecha->month)->where('anio', $clase->fecha->year));
        })->orderBy('id')->lockForUpdate()->get();

        if ($liquidaciones->contains(fn ($liq) => $liq->estaPagada())) {
            throw ValidationException::withMessages(['profesores' => 'La clase integra una liquidación pagada y no se puede modificar.']);
        }
        if ($liquidaciones->contains(fn ($liq) => $liq->estaCerrada())) {
            throw ValidationException::withMessages(['profesores' => 'Primero cancelá la liquidación cerrada antes de cambiar las profesoras de esta clase.']);
        }
    }

    public function show(int $id)
    {
        $clase = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores', 'asistencias.alumno'])->findOrFail($id);

        // T52 (DU-09): los cambios son siempre para adelante. La lista es la de quienes ya
        // estaban en el grupo el día de la clase (no aparece quien entró después), más quien
        // ya tiene una marca guardada en ella, aunque después haya cambiado de grupo o se
        // haya dado de baja.
        $alumnos = Alumno::where(fn ($q) => $q->enGrupoElDia($clase->grupo_id, $clase->fecha)->where('activo', true))
            ->orWhereIn('id', $clase->asistencias->pluck('alumno_id'))
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();
        $yaNoEstan = $alumnos
            ->filter(fn ($a) => !$a->activo || (int) $a->grupo_id !== (int) $clase->grupo_id)
            ->mapWithKeys(fn ($a) => [$a->id => $a->activo ? 'Cambió de grupo' : 'De baja']);

        $asistenciasMap = $clase->asistencias->keyBy('alumno_id');

        // Una sola vuelta para toda la lista (antes eran consultas por alumno).
        $infoSemana = $this->claseService->contarAsistenciasSemanaDeVarios(
            $alumnos->pluck('id')->all(),
            $clase->fecha->format('Y-m-d')
        );

        $profesoresDisponibles = Profesor::where('activo', true)->where('deporte_id', $clase->grupo->deporte_id)->orderBy('apellido')->get();
        $esAdmin    = Auth::user()->isAdmin();
        $esProfesor = Auth::user()->isProfesor();

        // Solo se pide motivo cuando se está CORRIGIENDO algo ya cargado en
        // una clase que ya pasó. Si nunca se cargó, es la primera carga y no
        // hay nada que justificar.
        $esPasada             = $clase->fecha->lt(today());
        $correccionAsistencia = $esPasada && $clase->asistencias->isNotEmpty();
        $correccionProfesores = $esPasada && $clase->profesores->isNotEmpty();
        $todaviaNoOcurrio     = $clase->todaviaNoOcurrio();

        return view('clases.show', compact(
            'clase', 'alumnos', 'yaNoEstan', 'asistenciasMap', 'infoSemana', 'profesoresDisponibles',
            'esAdmin', 'esProfesor', 'esPasada', 'correccionAsistencia', 'correccionProfesores',
            'todaviaNoOcurrio'
        ));
    }

    public function storeAsistencias(Request $request, int $id)
    {
        $clase  = Clase::findOrFail($id);
        $esJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($clase->cancelada) {
            if ($esJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'La clase está cancelada. No se pueden registrar asistencias.',
                ], 422);
            }
            return back()->with('error', 'La clase está cancelada.');
        }

        if ($clase->todaviaNoOcurrio()) {
            $mensaje = 'La clase todavía no ocurrió. La asistencia se toma desde el día de la clase.';
            if ($esJson) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }

        // Si la clase integra una liquidación cerrada, no se pueden tocar asistencias (FIN-12).
        if ($clase->integraLiquidacionCerrada()) {
            $mensaje = 'No se pueden modificar asistencias: la clase integra una liquidación cerrada.';
            if ($esJson) {
                return response()->json([
                    'success' => false,
                    'message' => $mensaje,
                ], 422);
            }
            return back()->with('error', $mensaje);
        }

        // El motivo justifica una CORRECCIÓN, no una carga. Si la clase ya
        // pasó pero nunca se le tomó lista, esto es la primera carga (se
        // traspapeló, se cargó tarde) y no hay nada que justificar. Solo se
        // pide motivo cuando ya existe una lista previa que se está cambiando.
        $esPasada           = $clase->fecha->lt(today());
        $yaTieneAsistencias = Asistencia::where('clase_id', $clase->id)->exists();
        $esCorreccion       = $esPasada && $yaTieneAsistencias;
        $motivoCorreccion   = trim((string) $request->input('motivo', ''));

        if ($esCorreccion && $motivoCorreccion === '') {
            $mensaje = 'Esta clase ya tenía lista tomada: indicá el motivo de la corrección.';
            if ($esJson) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }

        $items = $request->input('items', []);

        // Primera pasada: validar solapamiento de todos los presentes antes
        // de guardar nada (todo-o-nada, no queremos guardado parcial).
        $errores = [];
        // T52: también se acepta a quien ya tiene una marca en esta clase (cambió de grupo
        // o se dio de baja después): corregir la lista vieja no puede rechazarlo. Y no se
        // acepta a quien entró al grupo después de la clase (DU-09, siempre hacia adelante).
        $conMarca = Asistencia::where('clase_id', $clase->id)->pluck('alumno_id');
        // Mismo criterio que la pantalla (show): activos que ya estaban en el grupo ese día, o con marca.
        $alumnosDelGrupo = Alumno::where(fn ($q) => $q->where(fn ($g) => $g->enGrupoElDia($clase->grupo_id, $clase->fecha)->where('activo', true))->orWhereIn('id', $conMarca))
            ->whereIn('id', collect($items)->pluck('alumno_id')->map(fn($id) => (int) $id)->filter())
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        foreach ($items as $item) {
            $alumnoId = (int) ($item['alumno_id'] ?? 0);
            $presente = (bool) ($item['presente'] ?? false);

            if (!in_array($alumnoId, $alumnosDelGrupo, true)) {
                $errores[] = 'El alumno indicado no pertenece al grupo de la clase.';
                continue;
            }

            if ($presente) {
                $existente = Asistencia::where('clase_id', $clase->id)->where('alumno_id', $alumnoId)->first();
                $chequeo = $this->claseService->verificarDisponibilidadAlumno($clase->id, $alumnoId, $existente?->id);
                if (!$chequeo['puede_asistir']) {
                    $errores[] = $chequeo['razon'];
                }
            }
        }

        if (!empty($errores)) {
            $mensaje = implode(' ', $errores);
            if ($esJson) {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }

        // Segunda pasada: guardar como una sola unidad.
        DB::transaction(function () use ($items, $clase, $esCorreccion, $motivoCorreccion) {
            foreach ($items as $item) {
                $alumnoId = (int) ($item['alumno_id'] ?? 0);
                $presente  = (bool) ($item['presente'] ?? false);
                $motivo    = in_array($item['motivo_exceso'] ?? '', ['EXTRA', 'RECUPERA'])
                    ? $item['motivo_exceso']
                    : 'EXTRA';

                $datosAsistencia = ['presente' => $presente];
                if ($esCorreccion) {
                    $datosAsistencia['motivo_correccion'] = $motivoCorreccion;
                }

                $asistencia = Asistencia::updateOrCreate(
                    ['clase_id' => $clase->id, 'alumno_id' => $alumnoId],
                    $datosAsistencia
                );

                if ($presente) {
                    $info = $this->claseService->contarAsistenciasSemana(
                        $alumnoId,
                        $clase->fecha->format('Y-m-d')
                    );
                    if ($info['excede']) {
                        AsistenciaExceso::updateOrCreate(
                            ['asistencia_id' => $asistencia->id],
                            [
                                'alumno_id'  => $alumnoId,
                                'fecha_clase' => $clase->fecha->format('Y-m-d'),
                                'motivo'     => $motivo,
                            ]
                        );
                    }
                }
            }
        });

        if ($esJson) {
            return response()->json(['success' => true, 'message' => 'Asistencias guardadas.']);
        }

        return redirect()->route('web.clases.show', $clase->id)
            ->with('success', 'Asistencias guardadas.');
    }

    public function edit(int $id)
    {
        $clase      = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores'])->findOrFail($id);
        $profesores = Profesor::where('activo', true)->where('deporte_id', $clase->grupo->deporte_id)->orderBy('apellido')->get();

        return view('clases.edit', compact('clase', 'profesores'));
    }

    public function update(Request $request, int $id)
    {
        $clase = DB::transaction(function () use ($request, $id) {
            $clase = Clase::whereKey($id)->lockForUpdate()->firstOrFail();
            $pasada = $clase->fecha->lt(today());
            $validated = $request->validate([
                'fecha' => 'required|date_format:Y-m-d',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'motivo' => 'nullable|string|max:255',
                'profesores' => $pasada ? 'exclude' : 'required|array|min:1',
                'profesores.*' => $pasada ? 'exclude' : 'integer|exists:profesores,id',
            ], ['profesores.required' => 'Seleccioná al menos una profesora activa del deporte de la clase.']);
            $fechaCambio = $validated['fecha'] !== $clase->fecha->format('Y-m-d');
            $horarioCambio = $validated['hora_inicio'] !== $clase->hora_inicio->format('H:i')
                || $validated['hora_fin'] !== $clase->hora_fin->format('H:i');
            if (($pasada && $fechaCambio) || (!$pasada && $validated['fecha'] < today()->format('Y-m-d'))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'fecha' => 'No se puede cambiar la fecha de una clase pasada ni mover una clase hacia el pasado.',
                ]);
            }
            if ($pasada && $horarioCambio) {
                if ($clase->integraLiquidacionCerrada()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'hora_inicio' => 'No se puede cambiar el horario: la clase integra una liquidación cerrada.',
                    ]);
                }
                $motivo = trim($validated['motivo'] ?? '');
                if ($motivo === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'motivo' => 'Indicá el motivo del cambio de horario.',
                    ]);
                }
                $clase->motivo_cambio_horario = $motivo;
            }
            $actuales = $clase->profesores()->pluck('profesores.id')->all();
            $finales = $pasada ? $actuales : $this->validarProfesoresDelDeporte($request, (int) $clase->grupo->deporte_id);
            if (array_diff($actuales, $finales) || array_diff($finales, $actuales)) {
                $this->validarCambioProfesores($clase);
            }
            $clase->fill(collect($validated)->only(['fecha', 'hora_inicio', 'hora_fin'])->all())->save();

            // Los verificadores leen el horario nuevo; cualquier rechazo revierte todo.
            $aValidar = ($fechaCambio || $horarioCambio) ? $finales : array_diff($finales, $actuales);
            foreach ($aValidar as $profesorId) {
                $chequeo = $this->claseService->verificarDisponibilidadProfesor($clase->id, (int) $profesorId);
                if (!$chequeo['puede_asignar']) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['profesores' => $chequeo['razon']]);
                }
            }
            if ($fechaCambio || $horarioCambio) {
                foreach ($clase->asistencias()->where('presente', true)->get() as $asistencia) {
                    $chequeo = $this->claseService->verificarDisponibilidadAlumno($clase->id, $asistencia->alumno_id, $asistencia->id);
                    if (!$chequeo['puede_asistir']) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['hora_inicio' => $chequeo['razon']]);
                    }
                }
            }
            if (!$pasada) {
                $clase->profesores()->sync($finales);
            }
            return $clase;
        });

        return redirect()->route('web.clases.show', $clase->id)
            ->with('success', 'Clase actualizada correctamente.');
    }

    public function toggleCancelada(Request $request, int $id)
    {
        // Cancelar/reactivar una clase es acción de admin/operativo (dominio
        // "clases" de PERMISOS-ROLES.md), no del profesor. El botón ya estaba
        // oculto para el profesor en la vista, pero el endpoint no lo
        // exigía — cualquiera podía llamarlo directo. UP3.0 / S9-like gap.
        if (Auth::user()->isProfesor()) {
            abort(403, 'No tenés permiso para esta acción.');
        }

        $clase = Clase::findOrFail($id);
        $esJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!$clase->cancelada) {
            // T90 / DU-30: una clase que integra una liquidación cerrada o pagada no se cancela;
            // la liquidación quedaría pagando una clase cancelada. La pantalla muestra este texto.
            if ($clase->integraLiquidacionCerrada()) {
                $mensaje = 'No se puede cancelar: la clase está en una liquidación cerrada o pagada.';
                if ($esJson) {
                    return response()->json(['success' => false, 'message' => $mensaje], 422);
                }
                return back()->with('error', $mensaje);
            }
            $request->validate(['motivo_cancelacion' => 'required|string|max:255']);
            $motivo = $request->input('motivo_cancelacion');

            if ($request->boolean('cancelar_serie') && $clase->serie_id) {
                $count = Clase::where('serie_id', $clase->serie_id)
                    ->where('cancelada', false)
                    ->whereDate('fecha', '>=', today())
                    ->update([
                        'cancelada'          => true,
                        'motivo_cancelacion' => $motivo,
                    ]);
                $mensaje = "{$count} clase(s) de la serie canceladas.";
            } else {
                $clase->update([
                    'cancelada'          => true,
                    'motivo_cancelacion' => $motivo,
                ]);
                $mensaje = 'Clase cancelada.';
            }
        } else {
            // Reactivar: solo admin
            if (Auth::user()->rol !== 'ADMIN') {
                if ($esJson) {
                    return response()->json(['success' => false, 'message' => 'Sin permisos para reactivar.'], 403);
                }
                return back()->with('error', 'Sin permisos para reactivar la clase.');
            }
            $clase->update(['cancelada' => false, 'motivo_cancelacion' => null]);
            $mensaje = 'Clase reactivada.';
        }

        if ($esJson) {
            return response()->json(['success' => true, 'message' => $mensaje, 'cancelada' => $clase->cancelada]);
        }
        return back()->with('success', $mensaje);
    }

    public function actualizarProfesores(Request $request, int $id)
    {
        // Reasignar profesores es acción de admin/operativo, no del propio
        // profesor. Mismo motivo que toggleCancelada() arriba.
        if (Auth::user()->isProfesor()) {
            abort(403, 'No tenés permiso para esta acción.');
        }

        return DB::transaction(function () use ($request, $id) {
            $clase    = Clase::whereKey($id)->lockForUpdate()->firstOrFail();
            $esPasada = $clase->fecha->lt(today());

            if ($esPasada && !Auth::user()->isAdmin()) {
                return response()->json(['success' => false, 'message' => 'Esta clase ya pasó: solo un administrador puede cambiar sus profesores.'], 403);
            }
            $this->validarCambioProfesores($clase);
            $profesoresIds = $this->validarProfesoresDelDeporte($request, (int) $clase->grupo->deporte_id);
            $actualesIds   = $clase->profesores()->pluck('profesores.id')->toArray();
            $agregados     = array_diff($profesoresIds, $actualesIds);

            // Igual que en las asistencias: el motivo justifica un CAMBIO. Si la
            // clase pasó sin ningún profesor asignado, esto es la primera carga
            // (quedó sin cargar en su momento) y no hay nada que justificar.
            $esCorreccion = $esPasada && !empty($actualesIds);
            $motivo       = trim((string) $request->input('motivo', ''));

            if ($esCorreccion && $motivo === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta clase ya tenía profesor asignado: indicá el motivo del cambio.',
                ], 422);
            }

            // Validar solapamiento solo para los profesores nuevos que se agregan.
            foreach ($agregados as $profesorId) {
                $chequeo = $this->claseService->verificarDisponibilidadProfesor($clase->id, $profesorId);
                if (!$chequeo['puede_asignar']) {
                    return response()->json(['success' => false, 'message' => $chequeo['razon']], 422);
                }
            }

            $clase->profesores()->sync($profesoresIds);

            if ($esCorreccion) {
                $clase->update(['motivo_cambio_profesor' => $motivo]);
            }

            $textoProfesores = $clase->profesores()->orderBy('apellido')->get()
                ->map(fn($p) => $p->apellido . ', ' . $p->nombre)
                ->implode(' · ') ?: 'Sin asignar';

            return response()->json([
                'success'    => true,
                'message'    => 'Profesores actualizados.',
                'profesores' => $textoProfesores,
            ]);
        });
    }

    public function toggleValidada(int $id)
    {
        $clase = Clase::findOrFail($id);

        if ($clase->integraLiquidacionCerrada()) {
            $mensaje = 'No se puede modificar la validación: la clase integra una liquidación cerrada.';
            if (request()->expectsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }

        $clase->update(['validada_para_liquidacion' => !$clase->validada_para_liquidacion]);

        $estado = $clase->validada_para_liquidacion ? 'validada para liquidación' : 'desmarcada de validación';
        return back()->with('success', "Clase {$estado}.");
    }
}
