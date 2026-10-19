<?php

namespace App\Http\Controllers;

use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Liquidacion;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\Rubro;
use App\Models\TipoCaja;
use App\Services\CashflowService;
use App\Rules\ImportePesos;
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
        $movimientos = (clone $base)->with([
            'subrubro.rubro',
            'tipoCaja',
            'usuarioAdmin',
            'movimientoOperativo.alumno',
            'movimientoOperativo.usuario',
        ])
            ->when($tipo, fn($q) => $q->whereHas('subrubro.rubro', fn($r) => $r->where('tipo', $tipo)))
            ->orderBy('fecha', 'desc')
            ->paginate(30)
            ->withQueryString();

        // T28: Resolver alumno, usuario que registró y validador de caja para cada movimiento
        $pagosIds = $movimientos->getCollection()
            ->where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)
            ->pluck('referencia_id')
            ->filter()
            ->unique();
        $pagos = $pagosIds->isEmpty()
            ? collect()
            : Pago::with('alumno')->whereIn('id', $pagosIds)->get()->keyBy('id');

        $cajaIds = $movimientos->getCollection()
            ->where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->pluck('referencia_id')
            ->filter()
            ->unique();

        $cajas = $cajaIds->isEmpty()
            ? collect()
            : CajaOperativa::with('usuarioAdminValidacion')->whereIn('id', $cajaIds)->get()->keyBy('id');

        // Pool de movimientos operativos únicamente para renglones viejos (sin movimiento_operativo_id)
        $cajaIdsViejas = $movimientos->getCollection()
            ->where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->whereNull('movimiento_operativo_id')
            ->pluck('referencia_id')
            ->filter()
            ->unique();

        $movsOperativosPorCaja = $cajaIdsViejas->isEmpty()
            ? []
            : MovimientoOperativo::with(['alumno', 'usuario'])
                ->whereIn('caja_operativa_id', $cajaIdsViejas)
                ->activos()
                ->get()
                ->groupBy('caja_operativa_id')
                ->map(fn($g) => $g->values()->all())
                ->all();

        foreach ($movimientos->getCollection() as $mov) {
            $alumnoNombre = null;
            $quienRegistro = null;
            $validadorNombre = null;
            $validadorIniciales = null;

            if ($mov->referencia_tipo === CashflowMovimiento::REF_CAJA) {
                // Datos de quién validó la caja
                $caja = $cajas->get($mov->referencia_id);
                if ($caja && $caja->usuarioAdminValidacion) {
                    $validadorNombre = $caja->usuarioAdminValidacion->name;
                    $validadorIniciales = $caja->usuarioAdminValidacion->iniciales();
                }

                // 1. Vínculo directo guardado (certeza absoluta sin mirar texto ni heurística)
                if ($mov->movimiento_operativo_id && $mov->movimientoOperativo) {
                    $mo = $mov->movimientoOperativo;
                    if ($mo->alumno) {
                        $alumnoNombre = $mo->alumno->apellido . ', ' . $mo->alumno->nombre;
                    }
                    $quienRegistro = $mo->usuario?->name;
                } elseif (isset($movsOperativosPorCaja[$mov->referencia_id])) {
                    // 2. Renglón viejo de caja validada antes del cambio: coincidencia exacta o nada
                    $pool = &$movsOperativosPorCaja[$mov->referencia_id];
                    $matchKey = null;

                    foreach ($pool as $k => $mo) {
                        $montoMatch = abs((float) $mo->monto) == abs((float) $mov->monto);
                        $subrubroMatch = (int) $mo->subrubro_id === (int) $mov->subrubro_id;
                        $obsMatch = (string) $mo->observaciones === (string) $mov->observaciones;

                        if ($montoMatch && $subrubroMatch && $obsMatch) {
                            $matchKey = $k;
                            break;
                        }
                    }

                    if ($matchKey !== null) {
                        $mo = $pool[$matchKey];
                        unset($pool[$matchKey]);
                        if ($mo->alumno) {
                            $alumnoNombre = $mo->alumno->apellido . ', ' . $mo->alumno->nombre;
                        }
                        $quienRegistro = $mo->usuario?->name;
                    }
                    // Sin segundo intento por importe y rubro: si no hay certeza, no inventa un alumno.
                    unset($pool);
                }
            } elseif ($mov->referencia_tipo === CashflowMovimiento::REF_PAGO_CUOTA) {
                $pago = $pagos->get($mov->referencia_id);
                if ($pago && $pago->alumno) {
                    $alumnoNombre = $pago->alumno->apellido . ', ' . $pago->alumno->nombre;
                }
                $quienRegistro = $mov->usuarioAdmin?->name;
            }

            $quienRegistro ??= $mov->usuarioAdmin?->name;
            $mov->alumnoNombre = $alumnoNombre;
            $mov->quienRegistro = $quienRegistro;
            $mov->validadorNombre = $validadorNombre;
            $mov->validadorIniciales = $validadorIniciales;
        }

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

        $correccionesDisponibles = \App\Services\CashflowCorreccionService::disponible();
        $enCajasEditables = MovimientoOperativo::activos()->with(['cajaOperativa', 'subrubro', 'usuario'])
            ->whereHas('cajaOperativa', fn ($q) => $q->whereIn('estado', ['ABIERTA', 'RECHAZADA']))
            ->orderByDesc('fecha')->paginate(10, ['*'], 'cajas_page')->withQueryString();
        $liquidacionesPendientes = Liquidacion::with('profesor')->where('estado_pago', 'PENDIENTE')
            ->whereIn('estado', ['ABIERTA', 'CERRADA'])->orderByDesc('anio')->orderByDesc('mes')
            ->paginate(10, ['*'], 'liquidaciones_page')->withQueryString();
        foreach ($movimientos as $mov) {
            $origen = match ($mov->referencia_tipo) {
                CashflowMovimiento::REF_CAJA => 'Caja validada',
                CashflowMovimiento::REF_PAGO_CUOTA => 'Cobro del dueño',
                CashflowMovimiento::REF_LIQUIDACION => 'Liquidación pagada',
                CashflowMovimiento::REF_CORRECCION => 'Anulación de directo',
                default => match ($mov->reporte_clasificacion) {'APORTE' => 'Aporte', 'RETIRO' => 'Retiro', default => 'Directo'},
            };
            $acciones = [];
            if ($correccionesDisponibles && $mov->puedeCorregirse()) {
                $acciones = [
                    ['nombre' => 'Editar', 'url' => route('web.cashflow.movimientos.editar', $mov->id)],
                    ['nombre' => 'Eliminar', 'url' => route('web.cashflow.movimientos.eliminar', $mov->id)],
                ];
            } else {
                $pago = $mov->referencia_tipo === CashflowMovimiento::REF_PAGO_CUOTA ? $pagos->get($mov->referencia_id) : null;
                $url = match ($mov->referencia_tipo) {
                    CashflowMovimiento::REF_CAJA => route('web.caja.detalle', $mov->referencia_id),
                    CashflowMovimiento::REF_LIQUIDACION => route('web.liquidaciones.show', $mov->referencia_id),
                    default => $pago ? route('web.alumnos.show', $pago->alumno_id)
                        : ($correccionesDisponibles ? route('web.cashflow.movimientos.show', $mov->id) : null),
                };
                if ($url) $acciones[] = ['nombre' => $pago && $pago->estado !== 'ANULADO' ? 'Anular' : 'Ver', 'url' => $url];
            }
            $mov->origenCorreccion = $origen.($mov->anulado_at ? ' · Anulado' : '');
            $mov->accionesCorreccion = $acciones;
        }

        return view('cashflow.index', compact(
            'movimientos', 'tiposCaja', 'aniosDisponibles',
            'anio', 'mes', 'tipoCajaId', 'tipo',
            'totalIngresos', 'totalEgresos', 'modo', 'fechaReferencia', 'periodoTexto',
            'correccionesDisponibles', 'enCajasEditables', 'liquidacionesPendientes'
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
        ImportePesos::preparar($request, ['monto']);
        $request->validate([
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'subrubro_id'   => 'required|exists:subrubros,id',
            'monto'         => ['required', 'numeric', 'min:0.01', new ImportePesos],
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
                    monto: \App\Support\FormatoImporte::pesos((float) $request->input("monto")),
                    quienLoCargo: Auth::user()?->name,
                );
            }
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e))->withInput();
        }

        return redirect()->route('web.cashflow.index')->with('success', 'Movimiento directo registrado en cashflow.');
    }
}
