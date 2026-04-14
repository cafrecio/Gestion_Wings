<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Profesor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClaseWebController extends Controller
{
    public function index(Request $request)
    {
        // Default: current week Monday to Sunday
        $fechaDesde = $request->filled('fecha_desde')
            ? $request->input('fecha_desde')
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        $fechaHasta = $request->filled('fecha_hasta')
            ? $request->input('fecha_hasta')
            : Carbon::now()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        $query = Clase::with(['grupo.deporte', 'profesores'])
            ->orderBy('fecha')
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
        $grupos   = Grupo::with('deporte')->where('activo', true)->orderBy('nombre')->get();
        $deportes = Deporte::where('activo', true)->orderBy('nombre')->get();

        return view('clases.index', compact('clases', 'grupos', 'deportes', 'fechaDesde', 'fechaHasta'));
    }

    public function create()
    {
        $grupos    = Grupo::with(['deporte', 'planesActivos'])->where('activo', true)->orderBy('nombre')->get();
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
        $clase = Clase::with(['grupo.deporte', 'profesores'])->findOrFail($id);

        return view('clases.show', compact('clase'));
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

    public function toggleCancelada(int $id)
    {
        $clase = Clase::findOrFail($id);
        $clase->update(['cancelada' => !$clase->cancelada]);

        $estado = $clase->cancelada ? 'cancelada' : 'activada';
        return back()->with('success', "Clase {$estado} correctamente.");
    }

    public function toggleValidada(int $id)
    {
        $clase = Clase::findOrFail($id);
        $clase->update(['validada_para_liquidacion' => !$clase->validada_para_liquidacion]);

        $estado = $clase->validada_para_liquidacion ? 'validada para liquidación' : 'desmarcada de validación';
        return back()->with('success', "Clase {$estado}.");
    }
}
