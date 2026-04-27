<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\AsistenciaExceso;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Profesor;
use App\Services\ClaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaseWebController extends Controller
{
    public function __construct(private ClaseService $claseService) {}

    public function index(Request $request)
    {
        $esAdmin = Auth::user()->rol === 'ADMIN';
        $hoy = Carbon::now();
        $semanaDelMes = (int) ceil($hoy->day / 7);

        $defaultDesde = $hoy->copy()->subMonth()->startOfMonth()->format('Y-m-d');
        $defaultHasta = $semanaDelMes >= 3
            ? $hoy->copy()->addMonth()->endOfMonth()->format('Y-m-d')
            : $hoy->copy()->endOfMonth()->format('Y-m-d');

        $fechaDesde = $request->filled('fecha_desde')
            ? $request->input('fecha_desde')
            : $defaultDesde;

        $fechaHasta = $request->filled('fecha_hasta')
            ? $request->input('fecha_hasta')
            : $defaultHasta;

        if (!$esAdmin) {
            $limiteOperativo = $hoy->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            if ($fechaDesde < $limiteOperativo) {
                $fechaDesde = $limiteOperativo;
            }
        }

        $query = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores', 'asistencias'])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio');

        $query->whereBetween('fecha', [$fechaDesde, $fechaHasta]);

        if ($request->filled('grupo_id')) {
            $query->where('grupo_id', $request->input('grupo_id'));
        }

        if ($request->filled('deporte_id')) {
            $query->whereHas('grupo', function ($q) use ($request) {
                $q->where('deporte_id', $request->input('deporte_id'));
            });
        }

        if ($request->filled('cancelada')) {
            $query->where('cancelada', $request->boolean('cancelada'));
        }

        $clases   = $query->paginate(20)->withQueryString();
        $grupos   = Grupo::with(['deporte', 'nivel'])
            ->where('grupos.activo', true)
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')->orderBy('niveles.nombre')
            ->select('grupos.*')
            ->get();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('clases.index', compact('clases', 'grupos', 'deportes', 'fechaDesde', 'fechaHasta', 'esAdmin'));
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
        $tipo = $request->input('tipo_creacion', 'unica');

        $rules = [
            'grupo_id'    => 'required|exists:grupos,id',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'profesores'   => 'nullable|array',
            'profesores.*' => 'exists:profesores,id',
        ];

        if ($tipo === 'recurrente') {
            $rules['fecha_desde']   = 'required|date';
            $rules['fecha_hasta']   = 'required|date|after_or_equal:fecha_desde';
            $rules['dias_semana']   = 'required|array|min:1';
            $rules['dias_semana.*'] = 'in:0,1,2,3,4,5,6';
        } else {
            $rules['fecha'] = 'required|date|after_or_equal:today';
        }

        $validated = $request->validate($rules, [
            'grupo_id.required'    => 'Debe seleccionar un grupo.',
            'grupo_id.exists'      => 'El grupo seleccionado no existe.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required'    => 'La hora de fin es obligatoria.',
            'hora_fin.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
            'fecha.required'       => 'La fecha es obligatoria.',
            'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'fecha_desde.required' => 'La fecha de inicio es obligatoria.',
            'fecha_hasta.required' => 'La fecha de fin es obligatoria.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a fecha desde.',
            'dias_semana.required' => 'Debe seleccionar al menos un día de la semana.',
            'dias_semana.min'      => 'Debe seleccionar al menos un día de la semana.',
        ]);

        $profesoresIds = $request->input('profesores', []);
        $count = 0;

        if ($tipo === 'recurrente') {
            $diasSemana = array_map('intval', $request->input('dias_semana', []));
            $desde = Carbon::parse($validated['fecha_desde']);
            $hasta = Carbon::parse($validated['fecha_hasta']);

            $current = $desde->copy();
            while ($current->lte($hasta)) {
                // Carbon dayOfWeek: 0=Sunday, 1=Monday ... 6=Saturday
                if (in_array($current->dayOfWeek, $diasSemana)) {
                    $clase = Clase::create([
                        'grupo_id'    => $validated['grupo_id'],
                        'fecha'       => $current->format('Y-m-d'),
                        'hora_inicio' => $validated['hora_inicio'],
                        'hora_fin'    => $validated['hora_fin'],
                        'cancelada'   => false,
                        'validada_para_liquidacion' => false,
                    ]);
                    if (!empty($profesoresIds)) {
                        $clase->profesores()->sync($profesoresIds);
                    }
                    $count++;
                }
                $current->addDay();
            }
        } else {
            $clase = Clase::create([
                'grupo_id'    => $validated['grupo_id'],
                'fecha'       => $validated['fecha'],
                'hora_inicio' => $validated['hora_inicio'],
                'hora_fin'    => $validated['hora_fin'],
                'cancelada'   => false,
                'validada_para_liquidacion' => false,
            ]);
            if (!empty($profesoresIds)) {
                $clase->profesores()->sync($profesoresIds);
            }
            $count = 1;
        }

        return redirect()->route('web.clases.index')
            ->with('success', "{$count} clase(s) creada(s).");
    }

    public function show(int $id)
    {
        $clase = Clase::with(['grupo.deporte', 'grupo.nivel', 'profesores', 'asistencias.alumno'])->findOrFail($id);

        $alumnos = Alumno::where('grupo_id', $clase->grupo_id)
            ->where('activo', true)
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->get();

        $asistenciasMap = $clase->asistencias->keyBy('alumno_id');

        $infoSemana = [];
        foreach ($alumnos as $alumno) {
            $infoSemana[$alumno->id] = $this->claseService->contarAsistenciasSemana(
                $alumno->id,
                $clase->fecha->format('Y-m-d')
            );
        }

        $profesoresDisponibles = Profesor::where('activo', true)->orderBy('apellido')->get();
        $esAdmin = Auth::user()->rol === 'ADMIN';

        return view('clases.show', compact('clase', 'alumnos', 'asistenciasMap', 'infoSemana', 'profesoresDisponibles', 'esAdmin'));
    }

    public function storeAsistencias(Request $request, int $id)
    {
        $clase = Clase::findOrFail($id);

        if ($clase->cancelada) {
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'La clase está cancelada. No se pueden registrar asistencias.',
                ], 422);
            }
            return back()->with('error', 'La clase está cancelada.');
        }

        $items = $request->input('items', []);

        foreach ($items as $item) {
            $alumnoId = (int) ($item['alumno_id'] ?? 0);
            $presente  = (bool) ($item['presente'] ?? false);
            $motivo    = in_array($item['motivo_exceso'] ?? '', ['EXTRA', 'RECUPERA'])
                ? $item['motivo_exceso']
                : 'EXTRA';

            $asistencia = Asistencia::updateOrCreate(
                ['clase_id' => $clase->id, 'alumno_id' => $alumnoId],
                ['presente' => $presente]
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

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'message' => 'Asistencias guardadas.']);
        }

        return redirect()->route('web.clases.show', $clase->id)
            ->with('success', 'Asistencias guardadas.');
    }

    public function edit(int $id)
    {
        $clase      = Clase::with(['grupo.deporte', 'profesores'])->findOrFail($id);
        $profesores = Profesor::where('activo', true)->orderBy('apellido')->get();

        return view('clases.edit', compact('clase', 'profesores'));
    }

    public function update(Request $request, int $id)
    {
        $clase = Clase::findOrFail($id);

        $validated = $request->validate([
            'fecha'       => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
            'profesores'   => 'nullable|array',
            'profesores.*' => 'exists:profesores,id',
        ], [
            'fecha.required'       => 'La fecha es obligatoria.',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_fin.required'    => 'La hora de fin es obligatoria.',
            'hora_fin.after'       => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        $clase->update([
            'fecha'       => $validated['fecha'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin'    => $validated['hora_fin'],
        ]);

        $clase->profesores()->sync($request->input('profesores', []));

        return redirect()->route('web.clases.show', $clase->id)
            ->with('success', 'Clase actualizada correctamente.');
    }

    public function toggleCancelada(Request $request, int $id)
    {
        $clase = Clase::findOrFail($id);
        $esJson = $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if (!$clase->cancelada) {
            // Cancelar: requiere motivo
            $request->validate(['motivo_cancelacion' => 'required|string|max:255']);
            $clase->update([
                'cancelada'          => true,
                'motivo_cancelacion' => $request->input('motivo_cancelacion'),
            ]);
            $mensaje = 'Clase cancelada.';
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
        $clase = Clase::findOrFail($id);
        $profesoresIds = $request->input('profesores', []);
        $clase->profesores()->sync($profesoresIds);

        $textoProfesores = $clase->profesores()->orderBy('apellido')->get()
            ->map(fn($p) => $p->apellido . ', ' . $p->nombre)
            ->implode(' · ') ?: 'Sin asignar';

        return response()->json([
            'success'    => true,
            'message'    => 'Profesores actualizados.',
            'profesores' => $textoProfesores,
        ]);
    }

    public function toggleValidada(int $id)
    {
        $clase = Clase::findOrFail($id);
        $clase->update(['validada_para_liquidacion' => !$clase->validada_para_liquidacion]);

        $estado = $clase->validada_para_liquidacion ? 'validada para liquidación' : 'desmarcada de validación';
        return back()->with('success', "Clase {$estado}.");
    }
}
