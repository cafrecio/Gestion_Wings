<?php

namespace App\Http\Controllers;

use App\Models\CashflowMovimiento;
use App\Models\Rubro;
use App\Models\TipoCaja;
use App\Services\CashflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashflowWebController extends Controller
{
    public function __construct(private CashflowService $cashflowService) {}

    public function index(Request $request)
    {
        $request->validate([
            'periodo' => 'nullable|in:dia,semana,mes,anio',
            'fecha' => 'nullable|date_format:Y-m-d',
            'anio' => 'nullable|integer|between:1900,2100',
            'mes' => 'nullable|integer|between:1,12',
        ]);
        // Sin selector explícito, mantener los enlaces históricos Año/Mes.
        $modo = $request->input('periodo') ?? ($request->filled('mes') ? 'mes' : 'anio');
        $fechaReferencia = Carbon::parse($request->input('fecha') ?: today()->toDateString())->startOfDay();
        $anio = $request->filled('anio') ? (int) $request->input('anio') : $fechaReferencia->year;
        $mes = $request->filled('mes') ? (int) $request->input('mes') : $fechaReferencia->month;
        if (in_array($modo, ['mes', 'anio'], true)) {
            $primerDia = Carbon::create($anio, $mes, 1)->startOfDay();
            $fechaReferencia = $primerDia->day(min($fechaReferencia->day, $primerDia->daysInMonth));
        } else {
            $anio = $fechaReferencia->year;
            $mes = $fechaReferencia->month;
        }
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
        $tipoCajaId = $request->filled('tipo_caja_id') ? (int) $request->input('tipo_caja_id') : null;
        $tipo       = in_array($request->input('tipo'), ['INGRESO', 'EGRESO']) ? $request->input('tipo') : null;

        // Un mismo intervalo inclusivo para las filas y los dos totales.
        $base = CashflowMovimiento::query()
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->when($tipoCajaId, fn($q) => $q->where('tipo_caja_id', $tipoCajaId));
        $movimientos = (clone $base)->with(['subrubro.rubro', 'tipoCaja', 'usuarioAdmin'])
            ->when($tipo, fn($q) => $q->whereHas('subrubro.rubro', fn($r) => $r->where('tipo', $tipo)))
            ->orderBy('fecha', 'desc')
            ->paginate(30)
            ->withQueryString();

        $totalIngresos = (clone $base)
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'INGRESO'))
            ->sum('monto');

        // Los egresos se guardan negativos; para mostrar se usa el valor absoluto
        $totalEgresos = abs((clone $base)
            ->whereHas('subrubro.rubro', fn($q) => $q->where('tipo', 'EGRESO'))
            ->sum('monto'));

        $tiposCaja = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $aniosDisponibles = CashflowMovimiento::selectRaw('YEAR(fecha) as anio')
            ->distinct()->pluck('anio')->push($anio)->push(now()->year)->unique()->sortDesc()->values();

        return view('cashflow.index', compact(
            'movimientos', 'tiposCaja', 'aniosDisponibles',
            'anio', 'mes', 'tipoCajaId', 'tipo',
            'totalIngresos', 'totalEgresos', 'modo', 'fechaReferencia', 'periodoTexto'
        ));
    }

    public function create()
    {
        // El admin puede usar cualquier subrubro no reservado (los OPERATIVO son "ambos")
        $rubros = Rubro::with(['subrubros' => function ($q) {
            $q->where('es_reservado_sistema', false)
              ->orderBy('nombre');
        }])->orderBy('nombre')->get()
        ->filter(fn($r) => $r->subrubros->isNotEmpty())
        ->values();

        $tiposCaja    = TipoCaja::where('activo', true)->orderBy('nombre')->get();
        $subrubrosMap = $rubros->mapWithKeys(fn($r) => [
            $r->id => $r->subrubros->map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values(),
        ]);

        return view('cashflow.movimiento', compact('rubros', 'tiposCaja', 'subrubrosMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => 'required|numeric|min:0.01',
            'fecha'         => 'required|date|before_or_equal:today',
            'observaciones' => 'required|string|max:500',
        ]);

        $fechaMov = Carbon::parse($request->input('fecha'))->startOfDay();
        $esFechaVieja = $fechaMov->lessThan(now()->startOfMonth()->startOfDay());

        if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return back()->withInput()->with(
                'aviso_fecha_vieja',
                'Esta fecha es de un mes ya cerrado. El movimiento va a quedar registrado con esa fecha, pero entra en la caja de hoy y modificará el reporte de ese mes.'
            );
        }

        try {
            $this->cashflowService->registrarMovimientoAdmin([
                'usuario_admin_id' => Auth::id(),
                'tipo_caja_id'     => $request->input('tipo_caja_id'),
                'subrubro_id'      => $request->input('subrubro_id'),
                'monto'            => $request->input('monto'),
                'fecha'            => $request->input('fecha'),
                'observaciones'    => $request->input('observaciones'),
            ]);

            if ($esFechaVieja) {
                app(\App\Services\AvisoAdminService::class)->fechaVieja(
                    que: "Movimiento de cashflow",
                    fechaDelMovimiento: $request->input("fecha"),
                    monto: "$" . number_format((float) $request->input("monto"), 2, ",", "."),
                    quienLoCargo: Auth::user()?->name,
                );
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('web.cashflow.index')->with('success', 'Movimiento directo registrado en cashflow.');
    }
}
