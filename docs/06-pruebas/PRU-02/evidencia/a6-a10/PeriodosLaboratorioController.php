<?php
// Propuesta navegable exclusivamente en el laboratorio y su base descartable.
namespace Wings\Propuestas;

use App\Http\Controllers\CashflowWebController;
use App\Models\CashflowMovimiento;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodosLaboratorioController extends CashflowWebController
{
    public function index(Request $request)
    {
        abort_unless(app()->environment('testing') && getenv('A10_PERIODOS') === '1', 403);
        $request->validate([
            'periodo' => 'nullable|in:dia,semana,mes,anio',
            'fecha' => 'nullable|date_format:Y-m-d',
            'anio' => 'nullable|integer|between:1900,2100',
            'mes' => 'nullable|integer|between:1,12',
        ]);
        $datos = parent::index($request)->getData();
        $modo = $request->input('periodo', 'mes');
        $fechaReferencia = Carbon::parse($request->input('fecha', '2026-10-08'))->startOfDay();
        $anio = $request->integer('anio', 2026);
        $mes = $request->integer('mes', 10);
        $inicio = match ($modo) {
            'dia' => $fechaReferencia->copy(),
            'semana' => $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY),
            'mes' => Carbon::create($anio, $mes, 1)->startOfDay(),
            'anio' => Carbon::create($anio, 1, 1)->startOfDay(),
        };
        $fin = match ($modo) {
            'dia' => $inicio->copy(),
            'semana' => $inicio->copy()->addDays(6),
            'mes' => $inicio->copy()->endOfMonth()->startOfDay(),
            'anio' => $inicio->copy()->endOfYear()->startOfDay(),
        };
        $periodoTexto = match ($modo) {
            'dia' => $inicio->locale('es')->translatedFormat('j \d\e F \d\e Y'),
            'semana' => 'Del '.$inicio->format('d/m/Y').' al '.$fin->format('d/m/Y'),
            'mes' => ucfirst($inicio->locale('es')->translatedFormat('F Y')),
            'anio' => 'Año '.$anio.' completo',
        };
        // El mismo intervalo inclusivo para filas y ambos totales; admite cruces de año/mes.
        $base = CashflowMovimiento::query()->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->when($datos['tipoCajaId'], fn ($q) => $q->where('tipo_caja_id', $datos['tipoCajaId']));
        $datos['movimientos'] = (clone $base)->with(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin'])
            ->when($datos['tipo'], fn ($q) => $q->whereHas('subrubro.rubro', fn ($r) => $r->where('tipo', $datos['tipo'])))
            ->orderBy('fecha', 'desc')->paginate(30)->withQueryString();
        $datos['totalIngresos'] = (clone $base)->whereHas('subrubro.rubro', fn ($q) => $q->where('tipo', 'INGRESO'))->sum('monto');
        $datos['totalEgresos'] = abs((clone $base)->whereHas('subrubro.rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->sum('monto'));
        $datos += compact('modo', 'fechaReferencia', 'inicio', 'fin', 'periodoTexto');
        $datos['anio'] = $anio;
        $datos['mes'] = $mes;
        return view('cashflow.index', $datos);
    }
}
