<?php

namespace App\Http\Controllers;

use App\Models\{Alumno, CashflowMovimiento, Clase, Deporte, MovimientoOperativo, Profesor, TipoCaja};
use App\Services\{ReporteAlumnosService, ReporteMensualService, ReporteSueldosService};
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class ReporteWebController extends Controller
{
    public function sueldos(Request $request, ReporteSueldosService $servicio)
    {
        $datos = $request->validate([
            'mes'=>['sometimes','required','date_format:Y-m','after_or_equal:1900-01','before_or_equal:'.today()->format('Y-m')],
            'deporte_id'=>['nullable','integer','exists:deportes,id'],
        ]);
        $mes = $datos['mes'] ?? today()->format('Y-m');
        $reporte = $servicio->obtener($mes,isset($datos['deporte_id']) ? (int)$datos['deporte_id'] : null);
        $evolucion = $servicio->evolucion($mes,$reporte['deporte_id']);
        $deportes = Deporte::orderBy('nombre')->get(['id','nombre']);
        $desde = min(substr((string)(Clase::min('fecha') ?? $mes),0,7),$mes);
        $meses = [];
        for ($p = CarbonImmutable::createFromFormat('!Y-m',today()->format('Y-m')); $p->format('Y-m') >= $desde; $p = $p->subMonth()) $meses[] = $p->format('Y-m');
        return view('reportes.sueldos',compact('reporte','evolucion','deportes','meses'));
    }

    public function alumnos(Request $request, ReporteAlumnosService $servicio)
    {
        $datos = $request->validate([
            'mes' => ['sometimes', 'required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:'.today()->format('Y-m')],
            'deporte_id' => ['nullable', 'integer', 'exists:deportes,id'],
        ]);
        $mes = $datos['mes'] ?? today()->format('Y-m');
        $reporte = $servicio->obtener($mes, isset($datos['deporte_id']) ? (int) $datos['deporte_id'] : null);
        $evolucion = $servicio->evolucion($mes, $reporte['deporte_id']);
        $deportes = Deporte::orderBy('nombre')->get(['id', 'nombre']);
        $desde = min(substr((string) (Clase::min('fecha') ?? $mes), 0, 7), $mes);
        $meses = [];
        for ($p = CarbonImmutable::createFromFormat('!Y-m', today()->format('Y-m')); $p->format('Y-m') >= $desde; $p = $p->subMonth()) {
            $meses[] = $p->format('Y-m');
        }
        return view('reportes.alumnos', compact('reporte', 'evolucion', 'deportes', 'meses'));
    }

    public function index(Request $request, ReporteMensualService $servicio)
    {
        $datos = $request->validate([
            'mes' => ['sometimes', 'required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before_or_equal:'.today()->format('Y-m')],
            'deporte_id' => ['nullable', 'integer', 'exists:deportes,id'],
        ]);
        abort_unless(Schema::hasTable('reporte_cobertura') && Schema::hasTable('reporte_eventos'), 503, 'Reportes requiere preparar su historial.');
        $mes = $datos['mes'] ?? today()->format('Y-m');
        $deporteId = isset($datos['deporte_id']) ? (int) $datos['deporte_id'] : null;
        $reporte = $servicio->obtener($mes, $deporteId);
        // El mes elegido cerrado participa en las tarjetas y en las seis muestras.
        $limite = $mes === today()->format('Y-m') ? $mes : CarbonImmutable::createFromFormat('!Y-m', $mes)->addMonth()->format('Y-m');
        $evolucion = $servicio->evolucion($limite, $deporteId);
        if ($mes < today()->format('Y-m') && !collect($evolucion)->contains('mes', $mes)) {
            $evolucion = collect($evolucion)->push($reporte)->take(-6)->values()->all();
        }
        $deportes = Deporte::orderBy('nombre')->get(['id', 'nombre']);
        $nombresCajas = TipoCaja::all()->mapWithKeys(fn ($caja) => [$caja->id => $caja->abreviatura ?: $caja->nombre]);
        $alumnos = Alumno::whereIn('id', array_column($reporte['deuda']['filas'], 'persona_id'))->get(['id', 'nombre', 'apellido'])->keyBy('id');
        $profesores = Profesor::whereIn('id', array_column($reporte['por_pagar']['filas'], 'persona_id'))->get(['id', 'nombre', 'apellido'])->keyBy('id');
        $desde = collect([CashflowMovimiento::min('fecha'), MovimientoOperativo::min('fecha'), DB::table('reporte_cobertura')->where('id', 1)->value('desde'), $mes])
            ->filter()->map(fn ($f) => substr((string) $f, 0, 7))->min();
        $meses = [];
        for ($cursor = CarbonImmutable::createFromFormat('!Y-m', today()->format('Y-m')); $cursor->format('Y-m') >= $desde; $cursor = $cursor->subMonth()) {
            $meses[] = $cursor->format('Y-m');
        }

        return view('reportes.index', compact('reporte', 'evolucion', 'deportes', 'meses', 'nombresCajas', 'alumnos', 'profesores'));
    }
}
