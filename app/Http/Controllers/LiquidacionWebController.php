<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\CashflowMovimiento;
use App\Models\Clase;
use App\Models\Liquidacion;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LiquidacionWebController extends Controller
{
    public function __construct(
        private LiquidacionService $liquidacionService,
        private LiquidacionPagoService $liquidacionPagoService,
    ) {}

    public function index(Request $request)
    {
        $query = Liquidacion::with(['profesor.deporte'])
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('profesor_id')) {
            $query->where('profesor_id', $request->profesor_id);
        }
        if ($request->filled('mes')) {
            $query->where('mes', $request->mes);
        }
        if ($request->filled('anio')) {
            $query->where('anio', $request->anio);
        }
        if ($request->filled('estado')) {
            match ($request->estado) {
                'abierta' => $query->where('estado', 'ABIERTA'),
                'cerrada' => $query->where('estado', 'CERRADA'),
                'pagada'  => $query->where('estado_pago', 'PAGADA'),
                default   => null,
            };
        }

        $liquidaciones     = $query->paginate(20)->withQueryString();
        $profesores        = Profesor::where('activo', true)->orderBy('apellido')->get();
        $aniosDisponibles  = Liquidacion::selectRaw('DISTINCT anio')->orderBy('anio', 'desc')->pluck('anio');
        $meses = [
            1 => 'Enero',   2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
            5 => 'Mayo',    6 => 'Junio',     7 => 'Julio',     8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return view('liquidaciones.index', compact(
            'liquidaciones', 'profesores', 'aniosDisponibles', 'meses'
        ));
    }

    public function create()
    {
        $profesores = Profesor::where('activo', true)
            ->withCount(['clases as clases_count' => fn($q) =>
                $q->where('clases.cancelada', false)
                  ->where(function ($q2) {
                      $q2->where('clases.validada_para_liquidacion', true)
                         ->orWhereHas('asistencias', fn($a) => $a->where('presente', true));
                  })
                  ->whereNotIn('clases.id', function ($sub) {
                      $sub->select('referencia_id')
                          ->from('liquidacion_detalles')
                          ->where('tipo_referencia', 'clase')
                          ->whereIn('liquidacion_id', function ($lsub) {
                              $lsub->select('id')->from('liquidaciones')->where('estado', 'CERRADA');
                          });
                  })
            ])
            ->withCount(['clases as clases_sin_asistencia' => fn($q) =>
                $q->where('clases.cancelada', false)
                  ->whereDate('clases.fecha', '<', today())
                  ->where('clases.validada_para_liquidacion', false)
                  ->whereDoesntHave('asistencias', fn($a) => $a->where('presente', true))
            ])
            ->orderBy('apellido')
            ->get();

        $meses = [
            1 => 'Enero',   2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
            5 => 'Mayo',    6 => 'Junio',     7 => 'Julio',     8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $anioActual      = now()->year;
        $mesActual       = now()->month;
        $esUltimoDiaMes  = now()->isLastOfMonth();

        $profesoresJson = $profesores->mapWithKeys(fn($p) => [
            $p->id => [
                'nombre'         => $p->apellido . ', ' . $p->nombre,
                'sin_asistencia' => (int) $p->clases_sin_asistencia,
            ]
        ])->toArray();

        return view('liquidaciones.create', compact(
            'profesores', 'meses', 'anioActual', 'mesActual', 'esUltimoDiaMes', 'profesoresJson'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'profesor_id' => 'required|exists:profesores,id',
            'mes'         => 'required|integer|between:1,12',
            'anio'        => 'required|integer|min:2020|max:' . (now()->year + 1),
        ], [
            'profesor_id.required' => 'Seleccioná un profesor.',
            'mes.required'         => 'Seleccioná el mes.',
            'anio.required'        => 'Ingresá el año.',
        ]);

        try {
            // Si el admin eligió incluir las clases de hoy, las marca como validadas
            // para que el servicio las incluya en el cálculo
            if ($request->boolean('incluir_hoy')) {
                Clase::whereHas('profesores', fn($q) => $q->where('profesores.id', $request->profesor_id))
                    ->whereDate('fecha', today())
                    ->where('cancelada', false)
                    ->where('validada_para_liquidacion', false)
                    ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))
                    ->update(['validada_para_liquidacion' => true]);
            }

            $liquidacion = $this->liquidacionService->generarLiquidacionMensual(
                (int) $request->profesor_id,
                (int) $request->mes,
                (int) $request->anio,
            );

            return redirect()->route('web.liquidaciones.show', $liquidacion->id)
                ->with('success', 'Liquidación generada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(int $id)
    {
        $liquidacion = Liquidacion::with([
            'profesor.deporte',
            'detalles',
            'pagadaTipoCaja',
            'pagadaPorAdmin',
        ])->findOrFail($id);

        // Batch-load references to avoid N+1
        $ids = $liquidacion->detalles->pluck('referencia_id');
        if ($liquidacion->tipo === 'HORA') {
            $map = Clase::with('grupo')->whereIn('id', $ids)->get()->keyBy('id');
        } else {
            $map = Alumno::with('grupo')->whereIn('id', $ids)->get()->keyBy('id');
        }
        foreach ($liquidacion->detalles as $detalle) {
            $detalle->setRelation('referencia', $map->get($detalle->referencia_id));
        }

        $tiposCaja = TipoCaja::orderBy('nombre')->get();

        $saldosPorTipoCaja = CashflowMovimiento::selectRaw('tipo_caja_id, SUM(monto) as saldo')
            ->groupBy('tipo_caja_id')
            ->pluck('saldo', 'tipo_caja_id');

        return view('liquidaciones.show', compact('liquidacion', 'tiposCaja', 'saldosPorTipoCaja'));
    }

    public function cerrar(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->cerrarLiquidacion($id);
            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación cerrada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function recalcular(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->recalcularLiquidacion($id);
            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación recalculada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function eliminar(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->eliminarLiquidacion($id);
            return redirect()->route('web.liquidaciones.index')
                ->with('success', 'Liquidación eliminada.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pagar(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'fecha_pago'    => 'required|date',
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'observaciones' => 'nullable|string|max:500',
        ], [
            'fecha_pago.required'   => 'La fecha de pago es obligatoria.',
            'tipo_caja_id.required' => 'Seleccioná el tipo de caja.',
        ]);

        $tipoCaja    = TipoCaja::findOrFail($request->tipo_caja_id);
        $liquidacion = Liquidacion::with('profesor.deporte')->findOrFail($id);

        $saldoActual    = (float) CashflowMovimiento::where('tipo_caja_id', $tipoCaja->id)->sum('monto');
        $saldoResultante = $saldoActual - (float) $liquidacion->total_calculado;

        if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto) {
            return back()->with('error',
                "Saldo insuficiente en {$tipoCaja->nombre}. " .
                'Saldo disponible: $' . number_format($saldoActual, 0, ',', '.')
            );
        }

        $subrubro = $this->resolverSubrubroProfesor($liquidacion->profesor);

        if (!$subrubro) {
            return back()->with('error', 'No se encontró un subrubro válido para registrar el pago. Configure el rubro "Sueldos".');
        }

        try {
            $this->liquidacionPagoService->marcarComoPagada($id, [
                'fecha_pago'    => $request->fecha_pago,
                'tipo_caja_id'  => (int) $request->tipo_caja_id,
                'subrubro_id'   => $subrubro->id,
                'observaciones' => $request->observaciones,
                'admin_id'      => auth()->id(),
            ]);

            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación pagada. Movimiento registrado en cashflow.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function resolverSubrubroProfesor(\App\Models\Profesor $profesor): ?Subrubro
    {
        // Subrubro creado automáticamente al dar de alta al profesor
        $nombreSubrubro = ($profesor->deporte->nombre ?? 'Sin deporte')
            . '-' . $profesor->nombre . ' ' . $profesor->apellido;

        $subrubro = Subrubro::where('nombre', $nombreSubrubro)
            ->whereHas('rubro', fn($q) => $q->where('nombre', 'Sueldos'))
            ->first();

        if ($subrubro) {
            return $subrubro;
        }

        // Fallback: cualquier subrubro ADMIN bajo rubro Sueldos
        $rubroSueldos = Rubro::where('nombre', 'Sueldos')->first();

        if ($rubroSueldos) {
            return Subrubro::where('rubro_id', $rubroSueldos->id)
                ->where('permitido_para', 'ADMIN')
                ->first();
        }

        return null;
    }
}
