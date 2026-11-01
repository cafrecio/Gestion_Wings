<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\Pago;
use App\Rules\ImportePesos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GrupoWebController extends Controller
{
    public function index(Request $request)
    {
        $query = Grupo::with(['deporte', 'nivel', 'planesActivos'])
            ->join('deportes', 'grupos.deporte_id', '=', 'deportes.id')
            ->join('niveles', 'grupos.nivel_id', '=', 'niveles.id')
            ->orderBy('deportes.nombre')
            ->orderBy('niveles.nombre')
            ->select('grupos.*');

        if ($request->filled('deporte_id')) {
            $query->where('grupos.deporte_id', $request->input('deporte_id'));
        }

        $grupos   = $query->paginate(12)->withQueryString();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('grupos.index', compact('grupos', 'deportes'));
    }

    public function create()
    {
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();
        $niveles  = Nivel::where('activo', true)->orderBy('nombre')->get();

        return view('grupos.create', compact('deportes', 'niveles'));
    }

    public function store(Request $request)
    {
        ImportePesos::preparar($request, ['planes.*.precio_mensual']);
        $validator = Validator::make($request->all(), [
            'deporte_id'                     => 'required|exists:deportes,id',
            'nivel_id'                       => [
                'required',
                'exists:niveles,id',
                Rule::unique('grupos')->where(fn($q) =>
                    $q->where('deporte_id', $request->input('deporte_id'))
                ),
            ],
            'planes'                         => 'required|array|min:1',
            'planes.*.clases_por_semana'     => 'required|integer|min:1|max:7|distinct',
            'planes.*.precio_mensual'        => ['required', 'numeric', 'gt:0', new ImportePesos],
        ], [
            'planes.required'                       => 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.',
            'planes.array'                          => 'Las frecuencias deben enviarse como una lista.',
            'planes.min'                            => 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.',
            'planes.*.clases_por_semana.distinct'   => 'Hay dos filas con la misma frecuencia. Dejá una sola.',
            'deporte_id.required'                    => 'Debe seleccionar un deporte.',
            'deporte_id.exists'                      => 'El deporte seleccionado no existe.',
            'nivel_id.required'                      => 'Debe seleccionar un nivel.',
            'nivel_id.exists'                        => 'El nivel seleccionado no existe o está inactivo.',
            'nivel_id.unique'                        => 'Ya existe un grupo con ese deporte y nivel.',
            'planes.*.clases_por_semana.required'    => 'Indicá la frecuencia.',
            'planes.*.precio_mensual.required'       => 'Ingresá el precio.',
            'planes.*.precio_mensual.numeric'        => 'El precio debe ser un número.',
            'planes.*.precio_mensual.gt'             => 'El precio debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', $validator->errors()->first());
        }
        $validated = $validator->validated();

        $aviso = DB::transaction(function () use ($validated) {
            $aviso = $this->avisoNivelDestino((int) $validated['deporte_id'], (int) $validated['nivel_id']);
            if ($aviso !== null) {
                return $aviso;
            }

            $grupo = Grupo::create([
                'deporte_id' => $validated['deporte_id'],
                'nivel_id'   => $validated['nivel_id'],
                'activo'     => true,
            ]);

            foreach ($validated['planes'] as $planData) {
                GrupoPlan::create([
                    'grupo_id'          => $grupo->id,
                    'clases_por_semana' => $planData['clases_por_semana'],
                    'precio_mensual'    => $planData['precio_mensual'],
                    'activo'            => true,
                ]);
            }

            return null;
        });

        if ($aviso !== null) {
            return back()->withInput()->withErrors(['nivel_id' => $aviso])->with('error', $aviso);
        }

        return redirect()->route('web.grupos.index')
            ->with('success', 'Grupo creado correctamente.');
    }

    /**
     * T102: bloquea el nivel elegido para que nadie lo inactive mientras se le asigna un grupo,
     * y también serializa a dos que elijan el mismo. Va primero en la transacción: lo que se
     * lea después ya ve lo que el otro confirmó.
     */
    private function avisoNivelDestino(int $deporteId, int $nivelId, ?int $grupoId = null): ?string
    {
        $nivel = Nivel::whereKey($nivelId)->lockForUpdate()->first();
        if (!$nivel || !$nivel->activo) {
            return 'El nivel seleccionado no existe o está inactivo.';
        }

        $ocupado = Grupo::where('deporte_id', $deporteId)
            ->where('nivel_id', $nivelId)
            ->when($grupoId, fn($q) => $q->where('id', '!=', $grupoId))
            ->exists();

        return $ocupado ? 'Ya existe un grupo con ese deporte y nivel.' : null;
    }

    public function show(int $id)
    {
        $grupo = Grupo::with([
            'deporte',
            'nivel',
            'planes' => fn($q) => $q->where('activo', true)->orderBy('clases_por_semana'),
            'alumnos',
        ])->findOrFail($id);

        // Clases futuras no canceladas para el cronograma / clases próximas
        $clasesFuturas = $grupo->clases()
            ->where('cancelada', false)
            ->whereDate('fecha', '>=', today())
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->with('profesores')
            ->take(6)
            ->get();

        return view('grupos.show', compact('grupo', 'clasesFuturas'));
    }

    public function edit(int $id)
    {
        $grupo = Grupo::with([
            'deporte',
            'nivel',
            'planes' => fn($q) => $q->where('activo', true)->orderBy('clases_por_semana'),
        ])->findOrFail($id);

        // El nivel actual va siempre: un grupo inactivo pudo quedar en un nivel inactivo.
        $niveles = Nivel::where('activo', true)
            ->orWhere('id', $grupo->nivel_id)
            ->orderBy('nombre')
            ->get();

        return view('grupos.edit', compact('grupo', 'niveles'));
    }

    public function update(Request $request, int $id)
    {
        ImportePesos::preparar($request, ['planes.*.precio_mensual']);
        $grupo = Grupo::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nivel_id'                       => 'sometimes|required|integer|exists:niveles,id',
            'planes'                         => 'required|array|min:1',
            'planes.*.id'                    => ['nullable', 'integer', Rule::exists('grupo_planes', 'id')->where('grupo_id', $id)],
            'planes.*.clases_por_semana'     => 'required|integer|min:1|max:7|distinct',
            'planes.*.precio_mensual'        => ['required', 'numeric', 'gt:0', new ImportePesos],
        ], [
            'nivel_id.required'                     => 'Debe seleccionar un nivel.',
            'nivel_id.integer'                      => 'El nivel seleccionado no existe o está inactivo.',
            'nivel_id.exists'                       => 'El nivel seleccionado no existe o está inactivo.',
            'planes.required'                       => 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.',
            'planes.array'                          => 'Las frecuencias deben enviarse como una lista.',
            'planes.min'                            => 'El grupo debe tener al menos una frecuencia para poder cargar alumnos.',
            'planes.*.clases_por_semana.distinct'   => 'Hay dos filas con la misma frecuencia. Dejá una sola.',
            'planes.*.id.exists'                     => 'La frecuencia seleccionada ya no pertenece a este grupo. Volvé a cargar el formulario.',
            'planes.*.clases_por_semana.required'    => 'Indicá la frecuencia.',
            'planes.*.precio_mensual.required'       => 'Ingresá el precio.',
            'planes.*.precio_mensual.numeric'        => 'El precio debe ser un número.',
            'planes.*.precio_mensual.gt'             => 'El precio debe ser mayor a cero.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()
                ->with('error', $validator->errors()->first());
        }
        $validated = $validator->validated();

        return DB::transaction(function () use ($validated, $id) {
            // Serializar edición y eliminación sobre el mismo grupo.
            $grupo = Grupo::whereKey($id)->lockForUpdate()->firstOrFail();

            // T102: el deporte no cambia; el nivel sí. Alumnos, precios y clases siguen al grupo.
            $nivelNuevo = (int) ($validated['nivel_id'] ?? $grupo->nivel_id);
            $cambiaNivel = $nivelNuevo !== (int) $grupo->nivel_id;
            if ($cambiaNivel) {
                $aviso = $this->avisoNivelDestino((int) $grupo->deporte_id, $nivelNuevo, $id);
                if ($aviso !== null) {
                    return back()->withInput()->withErrors(['nivel_id' => $aviso])->with('error', $aviso);
                }
            }

            $planes = collect($validated['planes']);
            $idsSubmitted = $planes->pluck('id')->filter()->map(fn($v) => (int) $v)->all();

            // Una frecuencia pudo eliminarse o desactivarse mientras se esperaba el bloqueo (H2).
            if (!empty($idsSubmitted)) {
                $activosEnBd = GrupoPlan::where('grupo_id', $id)
                    ->whereIn('id', $idsSubmitted)
                    ->where('activo', true)
                    ->count();
                if ($activosEnBd !== count(array_unique($idsSubmitted))) {
                    return back()->withInput()->with('error', 'Las frecuencias del grupo cambiaron. Volvé a cargar el formulario.');
                }
            }

            // H4 y H5: validaciones de cambio de frecuencia en filas existentes enviadas con id
            foreach ($planes as $planData) {
                $planId = !empty($planData['id']) ? (int) $planData['id'] : null;
                if ($planId) {
                    $planActual = GrupoPlan::where('id', $planId)->where('grupo_id', $id)->first();
                    $nuevaFrecuencia = (int) $planData['clases_por_semana'];
                    if ($planActual && (int) $planActual->clases_por_semana !== $nuevaFrecuencia) {
                        // H5: si otra fila activa ya tiene esa frecuencia (ej. intercambio de frecuencias)
                        $yaExisteOtraActiva = GrupoPlan::where('grupo_id', $id)
                            ->where('activo', true)
                            ->where('clases_por_semana', $nuevaFrecuencia)
                            ->where('id', '!=', $planId)
                            ->exists();
                        if ($yaExisteOtraActiva) {
                            return back()->withInput()->with('error', 'Ya hay una fila con esa frecuencia. Cambialas de a una.');
                        }

                        // H4: si ya hay una fila desactivada con esa frecuencia
                        $yaExisteInactiva = GrupoPlan::where('grupo_id', $id)
                            ->where('activo', false)
                            ->where('clases_por_semana', $nuevaFrecuencia)
                            ->where('id', '!=', $planId)
                            ->exists();
                        if ($yaExisteInactiva) {
                            return back()->withInput()->with('error', "Ya hay una {$nuevaFrecuencia}x desactivada. Agregala con Nuevo en vez de cambiar esta fila.");
                        }
                    }
                }
            }

            // H1: Bloquear las filas de los planes que se sacan, en orden estable, antes de contar alumnos
            $planesRemovidos = GrupoPlan::where('grupo_id', $id)
                ->where('activo', true)
                ->when(!empty($idsSubmitted), fn($q) => $q->whereNotIn('id', $idsSubmitted))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            // Si alguna frecuencia removida tiene alumnos activos, no se guarda nada
            foreach ($planesRemovidos as $removido) {
                $alumnosHoy = AlumnoPlan::where('plan_id', $removido->id)->where('activo', true)->lockForUpdate()->count();
                if ($alumnosHoy > 0) {
                    $palabra = $alumnosHoy === 1 ? 'alumno' : 'alumnos';
                    return back()->withInput()->with('error', "{$removido->clases_por_semana}x por semana tiene {$alumnosHoy} {$palabra}: cambiales el plan antes.");
                }
            }

            // Después de los rechazos de arriba: si algo no se guarda, el nivel tampoco.
            if ($cambiaNivel) {
                $grupo->update(['nivel_id' => $nivelNuevo]);
            }

            // Procesar frecuencias removidas: desactivar si se usó antes, borrar si nunca se usó
            $desactivados = [];
            foreach ($planesRemovidos as $removido) {
                $usadoAntes = AlumnoPlan::where('plan_id', $removido->id)->exists()
                    || Pago::where('plan_id', $removido->id)->exists();

                if ($usadoAntes) {
                    $otraInactiva = GrupoPlan::where('grupo_id', $id)
                        ->where('clases_por_semana', $removido->clases_por_semana)
                        ->where('activo', false)
                        ->where('id', '!=', $removido->id)
                        ->exists();
                    if ($otraInactiva) {
                        return back()->withInput()->with('error', "Ya hay una {$removido->clases_por_semana}x desactivada en el grupo.");
                    }

                    $removido->update(['activo' => false]);
                    $desactivados[] = (int) $removido->clases_por_semana;
                } else {
                    $removido->delete();
                }
            }

            // Procesar frecuencias enviadas
            foreach ($planes as $planData) {
                $planId = !empty($planData['id']) ? (int) $planData['id'] : null;

                if ($planId) {
                    GrupoPlan::where('id', $planId)->where('grupo_id', $id)->firstOrFail()->update([
                        'clases_por_semana' => $planData['clases_por_semana'],
                        'precio_mensual'    => $planData['precio_mensual'],
                        'activo'            => true,
                    ]);
                } else {
                    $inactivo = GrupoPlan::where('grupo_id', $id)
                        ->where('clases_por_semana', $planData['clases_por_semana'])
                        ->where('activo', false)
                        ->orderBy('id')
                        ->first();

                    if ($inactivo) {
                        $inactivo->update([
                            'activo'         => true,
                            'precio_mensual' => $planData['precio_mensual'],
                        ]);
                    } else {
                        $activoExistente = GrupoPlan::where('grupo_id', $id)
                            ->where('clases_por_semana', $planData['clases_por_semana'])
                            ->where('activo', true)
                            ->first();

                        if ($activoExistente) {
                            $activoExistente->update([
                                'precio_mensual' => $planData['precio_mensual'],
                            ]);
                        } else {
                            GrupoPlan::create([
                                'grupo_id'          => $id,
                                'clases_por_semana' => $planData['clases_por_semana'],
                                'precio_mensual'    => $planData['precio_mensual'],
                                'activo'            => true,
                            ]);
                        }
                    }
                }
            }

            // H3: el aviso sale del resultado final: si la frecuencia sigue activa, «Grupo actualizado correctamente.»
            $frecuenciasEnviadas = $planes->pluck('clases_por_semana')->map(fn($v) => (int) $v)->all();
            $quedoAlgunaDesactivada = collect($desactivados)->contains(fn($f) => !in_array($f, $frecuenciasEnviadas, true));

            $mensajeExito = $quedoAlgunaDesactivada
                ? 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.'
                : 'Grupo actualizado correctamente.';

            return redirect()->route('web.grupos.index')
                ->with('success', $mensajeExito);
        });
    }

    public function checkDisponible(Request $request)
    {
        $deporteId = $request->input('deporte_id');
        $nivelId   = $request->input('nivel_id');

        if (!$deporteId || !$nivelId) {
            return response()->json(['disponible' => true]);
        }

        $query = Grupo::where('deporte_id', $deporteId)
                      ->where('nivel_id', $nivelId);

        if ($request->filled('grupo_id')) {
            $query->where('id', '!=', $request->input('grupo_id'));
        }

        return response()->json(['disponible' => !$query->exists()]);
    }

    public function toggleActivo(Request $request, int $id)
    {
        $grupo = Grupo::findOrFail($id);

        if ($grupo->activo) {
            $alumnosActivos = $grupo->alumnos()->where('activo', true)->count();
            $clasesFuturas = $grupo->clases()
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
                $mensaje = "No se puede desactivar el grupo porque tiene {$motivo}.";

                if ($request->expectsJson()) {
                    return response()->json(['error' => $mensaje], 422);
                }

                return back()->with('error', $mensaje);
            }

            $grupo->update(['activo' => false]);
        } else {
            // T102: un grupo activo no puede quedar en un nivel inactivo.
            $mensaje = DB::transaction(function () use ($grupo) {
                $grupo = Grupo::whereKey($grupo->id)->lockForUpdate()->firstOrFail();
                $nivel = Nivel::whereKey($grupo->nivel_id)->lockForUpdate()->first();
                if (!$nivel?->activo) {
                    return "No se puede activar el grupo porque el nivel «{$nivel?->nombre}» está inactivo. Activá el nivel o pasá el grupo a otro.";
                }
                $grupo->update(['activo' => true]);

                return null;
            });

            if ($mensaje !== null) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => $mensaje], 422);
                }

                return back()->with('error', $mensaje);
            }

            $grupo->refresh();
        }

        if ($request->expectsJson()) {
            return response()->json(['activo' => (bool) $grupo->activo]);
        }

        $estado = $grupo->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Grupo {$estado} correctamente.");
    }

    public function storePlan(Request $request, int $id)
    {
        ImportePesos::preparar($request, ['precio_mensual']);
        $grupo = Grupo::findOrFail($id);

        $validated = $request->validate([
            'clases_por_semana' => 'required|integer|min:1|max:7',
            'precio_mensual'    => ['required', 'numeric', 'gt:0', new ImportePesos],
        ], [
            'clases_por_semana.required' => 'Indicá la frecuencia.',
            'precio_mensual.required'    => 'Ingresá el precio.',
            'precio_mensual.numeric'     => 'El precio debe ser un número.',
            'precio_mensual.gt'          => 'El precio debe ser mayor a cero.',
        ]);

        return DB::transaction(function () use ($validated, $id) {
            $grupo = Grupo::whereKey($id)->lockForUpdate()->firstOrFail();

            $yaExisteActivo = GrupoPlan::where('grupo_id', $id)
                ->where('clases_por_semana', $validated['clases_por_semana'])
                ->where('activo', true)
                ->exists();

            if ($yaExisteActivo) {
                return back()->withErrors([
                    'clases_por_semana' => 'Ya existe un plan con esa frecuencia para este grupo.',
                ])->withInput();
            }

            // Si existe inactivo, reactivar esa misma fila con el nuevo precio
            $inactivo = GrupoPlan::where('grupo_id', $id)
                ->where('clases_por_semana', $validated['clases_por_semana'])
                ->where('activo', false)
                ->orderBy('id')
                ->first();

            if ($inactivo) {
                $inactivo->update([
                    'activo'         => true,
                    'precio_mensual' => $validated['precio_mensual'],
                ]);
            } else {
                GrupoPlan::create([
                    'grupo_id'          => $id,
                    'clases_por_semana' => $validated['clases_por_semana'],
                    'precio_mensual'    => $validated['precio_mensual'],
                    'activo'            => true,
                ]);
            }

            return redirect()->route('web.grupos.show', $id)->with('success', 'Plan agregado.');
        });
    }

    public function destroyPlan(int $planId)
    {
        $plan = GrupoPlan::findOrFail($planId);

        return DB::transaction(function () use ($planId) {
            $plan = GrupoPlan::whereKey($planId)->lockForUpdate()->firstOrFail();
            $grupo = Grupo::whereKey($plan->grupo_id)->lockForUpdate()->firstOrFail();

            if ($grupo->planesActivos()->count() <= 1) {
                return back()->with('error', 'No se puede eliminar: el grupo quedaría sin frecuencias y no se podrían cargar alumnos.');
            }

            $alumnosHoy = AlumnoPlan::where('plan_id', $planId)->where('activo', true)->count();
            if ($alumnosHoy > 0) {
                $palabra = $alumnosHoy === 1 ? 'alumno' : 'alumnos';
                return back()->with('error', "{$plan->clases_por_semana}x por semana tiene {$alumnosHoy} {$palabra}: cambiales el plan antes.");
            }

            $usadoAntes = AlumnoPlan::where('plan_id', $planId)->exists()
                || Pago::where('plan_id', $planId)->exists();

            $grupoId = $plan->grupo_id;

            if ($usadoAntes) {
                $otraInactiva = GrupoPlan::where('grupo_id', $grupoId)
                    ->where('clases_por_semana', $plan->clases_por_semana)
                    ->where('activo', false)
                    ->where('id', '!=', $planId)
                    ->exists();
                if ($otraInactiva) {
                    return back()->with('error', "Ya hay una {$plan->clases_por_semana}x desactivada en el grupo.");
                }

                $plan->update(['activo' => false]);

                return redirect()->route('web.grupos.show', $grupoId)
                    ->with('success', 'Frecuencia desactivada: ya no se ofrece. Lo anterior queda como está.');
            }

            $plan->delete();

            return redirect()->route('web.grupos.show', $grupoId)->with('success', 'Plan eliminado.');
        });
    }
}
