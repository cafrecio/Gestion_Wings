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
use App\Rules\ImportePesos;
use App\Services\LiquidacionPagoService;
use App\Services\LiquidacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'abierta'   => $query->where('estado', 'ABIERTA'),
                'cerrada'   => $query->where('estado', 'CERRADA'),
                'cancelada' => $query->where('estado', 'CANCELADA'),
                'pagada'    => $query->where('estado_pago', 'PAGADA'),
                default     => null,
            };
        }
        if ($request->input('pendientes') === '1') {
            // Mismo conjunto que el aviso de Inicio: cerradas, aún no pagadas,
            // de cualquier mes. La opción normal "cerrada" sigue incluyendo pagadas.
            $query->where('estado', 'CERRADA')->where('estado_pago', 'PENDIENTE');
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

    public function create(Request $request)
    {
        $meses = [
            1 => 'Enero',   2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
            5 => 'Mayo',    6 => 'Junio',     7 => 'Julio',     8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $anioActual      = (int) ($request->input('anio') ?? old('anio') ?? now()->year);
        $mesActual       = (int) ($request->input('mes') ?? old('mes') ?? now()->month);
        $esUltimoDiaMes  = now()->isLastOfMonth();

        $queryLiquidables = fn($q, $filtrarFecha = false) =>
            $q->where('clases.cancelada', false)
              ->when($filtrarFecha, fn($qf) => $qf->whereMonth('clases.fecha', $mesActual)->whereYear('clases.fecha', $anioActual))
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
              });

        $querySinAsistencia = fn($q, $filtrarFecha = false) =>
            $q->where('clases.cancelada', false)
              ->when($filtrarFecha, fn($qf) => $qf->whereMonth('clases.fecha', $mesActual)->whereYear('clases.fecha', $anioActual))
              ->whereDate('clases.fecha', '<', today())
              ->where('clases.validada_para_liquidacion', false)
              ->doesntHave('asistencias');

        $profesores = Profesor::where('activo', true)
            ->with('deporte')
            ->withCount([
                'clases as clases_count_total' => fn($q) => $queryLiquidables($q, false),
                'clases as clases_count' => fn($q) => $queryLiquidables($q, true),
                'clases as clases_sin_asistencia' => fn($q) => $querySinAsistencia($q, true),
            ])
            ->orderBy('apellido')
            ->get();

        $periodosLiquidables = DB::table('clases')
            ->join('clase_profesor', 'clases.id', '=', 'clase_profesor.clase_id')
            ->where('clases.cancelada', false)
            ->where(function ($q2) {
                $q2->where('clases.validada_para_liquidacion', true)
                   ->orWhereExists(function ($sub) {
                       $sub->select(DB::raw(1))
                           ->from('asistencias')
                           ->whereColumn('asistencias.clase_id', 'clases.id')
                           ->where('asistencias.presente', true);
                   });
            })
            ->whereNotIn('clases.id', function ($sub) {
                $sub->select('referencia_id')
                    ->from('liquidacion_detalles')
                    ->where('tipo_referencia', 'clase')
                    ->whereIn('liquidacion_id', function ($lsub) {
                        $lsub->select('id')->from('liquidaciones')->where('estado', 'CERRADA');
                    });
            })
            ->select(
                'clase_profesor.profesor_id',
                DB::raw('YEAR(clases.fecha) as anio'),
                DB::raw('MONTH(clases.fecha) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('clase_profesor.profesor_id', DB::raw('YEAR(clases.fecha)'), DB::raw('MONTH(clases.fecha)'))
            ->get();

        $periodosSinAsistencia = DB::table('clases')
            ->join('clase_profesor', 'clases.id', '=', 'clase_profesor.clase_id')
            ->where('clases.cancelada', false)
            ->whereDate('clases.fecha', '<', today())
            ->where('clases.validada_para_liquidacion', false)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('asistencias')
                    ->whereColumn('asistencias.clase_id', 'clases.id');
            })
            ->select(
                'clase_profesor.profesor_id',
                DB::raw('YEAR(clases.fecha) as anio'),
                DB::raw('MONTH(clases.fecha) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('clase_profesor.profesor_id', DB::raw('YEAR(clases.fecha)'), DB::raw('MONTH(clases.fecha)'))
            ->get();

        $profesoresJson = $profesores->mapWithKeys(function ($p) use ($periodosLiquidables, $periodosSinAsistencia) {
            $periodos = [];
            foreach ($periodosLiquidables->where('profesor_id', $p->id) as $row) {
                $key = $row->anio . '-' . $row->mes;
                $periodos[$key]['liquidables'] = (int) $row->total;
            }
            foreach ($periodosSinAsistencia->where('profesor_id', $p->id) as $row) {
                $key = $row->anio . '-' . $row->mes;
                if (!isset($periodos[$key])) {
                    $periodos[$key] = ['liquidables' => 0];
                }
                $periodos[$key]['sin_asistencia'] = (int) $row->total;
            }

            return [
                $p->id => [
                    'nombre'           => $p->apellido . ', ' . $p->nombre,
                    'total_pendientes' => (int) $p->clases_count_total,
                    'clases_count'     => (int) $p->clases_count,
                    'sin_asistencia'   => (int) $p->clases_sin_asistencia,
                    'periodos'         => $periodos,
                ]
            ];
        })->toArray();

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
            $liquidacion = DB::transaction(function () use ($request) {
                // Si el admin eligió incluir las clases de hoy, las marca como validadas
                // solo si hoy pertenece al período solicitado y no integran una liquidación cerrada.
                if ($request->boolean('incluir_hoy') && today()->month === (int) $request->mes && today()->year === (int) $request->anio) {
                    $clasesHoy = Clase::whereHas('profesores', fn($q) => $q->where('profesores.id', $request->profesor_id))
                        ->whereDate('fecha', today())
                        ->where('cancelada', false)
                        ->where('validada_para_liquidacion', false)
                        ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))
                        ->lockForUpdate()
                        ->get();

                    foreach ($clasesHoy as $clase) {
                        if (!$clase->integraLiquidacionCerrada()) {
                            $clase->update(['validada_para_liquidacion' => true]);
                        }
                    }
                }

                return $this->liquidacionService->generarLiquidacionMensual(
                    (int) $request->profesor_id,
                    (int) $request->mes,
                    (int) $request->anio,
                );
            });

            return redirect()->route('web.liquidaciones.show', $liquidacion->id)
                ->with('success', 'Liquidación generada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function show(int $id)
    {
        $liquidacion = Liquidacion::with([
            'profesor.deporte',
            'detalles.decisionAdmin',
            'pagadaTipoCaja',
            'pagadaPorAdmin',
            'usuarioCancelacion',
            'reemplazadaPor',
        ])->findOrFail($id);

        if ($liquidacion->tipo === Liquidacion::TIPO_COMISION && $liquidacion->porcentaje_comision_aplicado !== null) {
            $liquidacion->profesor->porcentaje_comision = $liquidacion->porcentaje_comision_aplicado;
            $liquidacion->profesor->syncOriginal();
        }

        if ($liquidacion->tipo === Liquidacion::TIPO_HORA && $liquidacion->valor_hora_aplicado !== null) {
            $liquidacion->profesor->valor_hora = $liquidacion->valor_hora_aplicado;
            $liquidacion->profesor->syncOriginal();
        }

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

        $tiposCaja = TipoCaja::where('activo', true)
            ->withSum('cashflowMovimientos as saldo_movimientos', 'monto')
            ->orderBy('nombre')
            ->get();

        $saldosPorTipoCaja = $tiposCaja->mapWithKeys(fn(TipoCaja $tipoCaja) => [
            $tipoCaja->id => (float) $tipoCaja->saldo_inicial + (float) $tipoCaja->saldo_movimientos,
        ]);

        return view('liquidaciones.show', compact('liquidacion', 'tiposCaja', 'saldosPorTipoCaja'));
    }

    public function cerrar(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->cerrarLiquidacion($id);
            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación cerrada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function decidirAtrasada(Request $request, int $id, int $detalleId): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'decision' => 'required|in:APROBADA,RECHAZADA',
            'decision_esperada' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
        ]);
        $liquidacion = $this->liquidacionService->decidirAtrasada($id, $detalleId, $datos['decision'],
            $request->user()->id, $datos['decision_esperada']);
        if ($request->expectsJson()) {
            // No ejecutar show(): alumnos, grupos, cuentas y demás datos no cambiaron.
            $detalle = $liquidacion->detalles->firstWhere('id', $detalleId);
            $detalle->load('decisionAdmin');
            $pesos = fn ($valor) => \App\Support\FormatoImporte::pesos((float) $valor);
            return response()->json([
                'detalle_id' => $detalle->id,
                'decision' => $detalle->decision_atrasada,
                'estado' => $detalle->decision_atrasada === 'APROBADA' ? 'Validada' : 'Rechazada',
                'autor' => ' · '.($detalle->decisionAdmin?->name ?? 'Administración').' · '.$detalle->decision_at?->format('d/m/Y H:i'),
                'importes' => [
                    'confirmado' => $pesos($liquidacion->total_confirmado),
                    'final' => $pesos($liquidacion->monto_a_pagar),
                    'ajuste' => $pesos((float) $liquidacion->monto_a_pagar - (float) $liquidacion->total_confirmado),
                ],
                'monto_final' => $liquidacion->monto_a_pagar,
                'monto_mostrado' => \App\Rules\ImportePesos::mostrar($liquidacion->monto_a_pagar),
                'acciones' => view('liquidaciones.partials.acciones', compact('liquidacion'))->render(),
                'revision' => view('liquidaciones.partials.revision-atrasadas', compact('liquidacion'))->render(),
            ]);
        }
        return redirect()->route('web.liquidaciones.show', $id)
            ->with('success', 'Decisión registrada. El recibo incluye solo las cuotas atrasadas validadas.');
    }

    public function recalcular(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->recalcularLiquidacion($id);
            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación recalculada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function cancelar(Request $request, int $id): RedirectResponse
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'motivo' => 'required|string|min:5|max:255',
        ], [
            'motivo.required' => 'Indicá el motivo de la cancelación.',
            'motivo.min'      => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        try {
            $this->liquidacionService->cancelarLiquidacion($id, $request->motivo, auth()->id());
            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación cancelada. Ya podés revisar asistencias y regenerarla.');
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function eliminar(int $id): RedirectResponse
    {
        try {
            $this->liquidacionService->eliminarLiquidacion($id);
            return redirect()->route('web.liquidaciones.index')
                ->with('success', 'Liquidación eliminada.');
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function pagar(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'fecha_pago'    => 'required|date|before_or_equal:today',
            'tipo_caja_id'  => 'required|exists:tipos_caja,id',
            'observaciones' => 'nullable|string|max:500',
            'monto_esperado'=> 'nullable|numeric|min:0',
        ], [
            'fecha_pago.required'        => 'La fecha de pago es obligatoria.',
            'fecha_pago.before_or_equal' => 'La fecha de pago no puede ser futura.',
            'tipo_caja_id.required'      => 'Seleccioná el tipo de caja.',
        ]);

        // FIN-09, igual que caja, cobro y Cashflow: una fecha de un mes anterior
        // cambia el resultado de ese mes, así que se confirma antes de guardar.
        $esFechaVieja = \Carbon\Carbon::parse($request->fecha_pago)->startOfDay()
            ->lessThan(now()->startOfMonth()->startOfDay());

        if ($esFechaVieja && !$request->boolean('confirmar_fecha_vieja')) {
            return back()->withInput()->with(
                'aviso_fecha_vieja',
                'Esta fecha es de un mes anterior. El pago va a quedar registrado con esa fecha y modificará el reporte de ese mes.'
            );
        }

        $tipoCaja    = TipoCaja::findOrFail($request->tipo_caja_id);
        $liquidacion = Liquidacion::with('profesor.deporte')->findOrFail($id);

        $saldoActual = (float) $tipoCaja->saldo_inicial
            + (float) CashflowMovimiento::where('tipo_caja_id', $tipoCaja->id)->sum('monto');
        $saldoResultante = $saldoActual - (float) $liquidacion->monto_a_pagar;

        if ($saldoResultante < 0 && !$tipoCaja->permite_descubierto) {
            return back()->with('error',
                "Saldo insuficiente en {$tipoCaja->nombre}. " .
                'Saldo disponible: ' . \App\Support\FormatoImporte::pesos($saldoActual)
            );
        }

        $subrubro = $this->resolverSubrubroProfesor($liquidacion->profesor);

        if (!$subrubro) {
            $prof = $liquidacion->profesor;
            return back()->with('error',
                "El profesor {$prof->nombre} {$prof->apellido} no tiene un subrubro de sueldos asignado. " .
                'Editá y guardá su ficha para regenerarlo antes de pagar la liquidación.'
            );
        }

        try {
            $resultado = $this->liquidacionPagoService->marcarComoPagada($id, [
                'fecha_pago'    => $request->fecha_pago,
                'tipo_caja_id'  => (int) $request->tipo_caja_id,
                'subrubro_id'   => $subrubro->id,
                'observaciones' => $request->observaciones,
                'admin_id'      => auth()->id(),
                'monto_esperado'=> $request->input('monto_esperado', $liquidacion->monto_a_pagar),
            ]);

            if ($esFechaVieja && !$resultado['ya_pagada']) {
                app(\App\Services\AvisoAdminService::class)->fechaVieja(
                    que: 'Pago de liquidación',
                    fechaDelMovimiento: $request->fecha_pago,
                    monto: \App\Support\FormatoImporte::pesos((float) $resultado['liquidacion']->monto_a_pagar),
                    quienLoCargo: auth()->user()?->name,
                );
            }

            return redirect()->route('web.liquidaciones.show', $id)
                ->with('success', 'Liquidación pagada. Movimiento registrado en cashflow.');
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    public function ajustar(Request $request, int $id): RedirectResponse
    {
        ImportePesos::preparar($request, ['monto_final']);
        abort_unless(Liquidacion::ajustesDisponibles(), 503, 'El ajuste final requiere preparar su registro antes de usarlo.');
        $datos = $request->validate([
            'monto_final'=>['required', 'numeric', 'min:0', 'max:9999999999.99', new ImportePesos],
            'monto_anterior'=>['required', 'numeric', 'min:0'],
            'motivo'=>['required', 'string', 'min:5', 'max:255'],
        ], ['monto_final.required'=>'Ingresá el monto final.', 'monto_final.regex'=>'Ingresá un importe válido, con hasta dos decimales.',
            'motivo.required'=>'Indicá el motivo del ajuste.', 'motivo.min'=>'El motivo debe tener al menos 5 caracteres.']);
        try {
            Liquidacion::findOrFail($id)->ajustarMontoFinal((string) $datos['monto_final'], auth()->id(), $datos['motivo'], (string) $datos['monto_anterior']);
            return redirect()->route('web.liquidaciones.show', $id)->with('success', 'Monto final actualizado. El cálculo original se conserva.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);
            return back()->withInput()->with('error', 'No se pudo guardar el ajuste. Recargá la liquidación e intentá nuevamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', \App\Support\AvisoError::texto($e));
        }
    }

    private function resolverSubrubroProfesor(\App\Models\Profesor $profesor): ?Subrubro
    {
        // D3: el subrubro de sueldos está vinculado al profesor por FK. Ya no
        // se reconstruye el nombre ni se cae a "cualquier subrubro de Sueldos"
        // (ese fallback podía imputar el sueldo al cajón de otro profesor).
        // Si el profesor no tiene subrubro asignado, se devuelve null y el
        // flujo de pago avisa y frena — nunca imputa a uno equivocado.
        return $profesor->subrubro;
    }
}
