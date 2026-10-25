<?php

namespace App\Services;

use App\Models\{Alumno, AlumnoRevisionCobranza, CajaOperativa, CargoAlumno, CashflowMovimiento, Clase, Liquidacion, MovimientoOperativo, Pago, TipoCaja};
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReporteMensualService
{
    /**
     * T79: dentro de un lote el historial de movimientos se lee una sola vez. Con dos años de
     * datos cada lectura tarda más de un segundo, y Reportes la repetía ocho veces. Fuera de un
     * lote cada llamada vuelve a leer la base: quien cambia datos y vuelve a pedir ve lo nuevo.
     */
    private bool $enLote = false;
    private ?Collection $movimientosDelLote = null;

    public function enLote(callable $calculo): mixed
    {
        if ($this->enLote) return $calculo();
        $this->enLote = true;
        try {
            return $calculo();
        } finally {
            $this->enLote = false;
            $this->movimientosDelLote = null;
        }
    }

    public function obtener(string $mes, ?int $deporteId = null): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) || $mes > today()->format('Y-m')) {
            throw new InvalidArgumentException('Elegí un mes actual o anterior.');
        }
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes);
        $fin = min($inicio->endOfMonth()->toDateString(), today()->toDateString());
        $filas = $this->movimientos()->filter(fn ($f) => $f['fecha'] <= $fin);
        $delMes = $filas->filter(fn ($f) => substr($f['fecha'], 0, 7) === $mes);
        $delMesEconomico = $this->filasEconomicas($filas)->where('periodo_ingreso', $mes);
        $enDeporte = fn ($f) => $deporteId === null || $f['deporte_id'] === $deporteId;
        $economicos = $delMesEconomico->filter(fn ($f) => $f['clasificacion'] === 'NEGOCIO' && $enDeporte($f));
        $ingresos = $economicos->where('tipo', 'INGRESO')->sum('centavos');
        $egresos = -$economicos->where('tipo', 'EGRESO')->sum('centavos');
        $desconocidos = $delMes->filter(fn ($f) => $f['clasificacion'] === null);
        $deuda = $this->deudaDeAlumnos($fin, $mes, $deporteId);
        $porPagar = $this->saldoHistorico('LIQUIDACION', $fin, $mes, $deporteId);
        $cajas = $this->saldosPorCaja($fin);
        $hasta = DB::table('reporte_cobertura')->where('id', 1)->value('desde');
        return [
            'mes' => $mes, 'fecha_corte' => $fin, 'deporte_id' => $deporteId,
            'ingresos' => $ingresos, 'egresos' => $egresos,
            'resultado' => $desconocidos->isEmpty() ? $ingresos - $egresos : null,
            'resultado_global' => $delMes->sum('centavos'),
            'sin_clasificar' => $desconocidos->count(),
            'gastos_club' => -$delMes->filter(fn ($f) => $f['clasificacion'] === 'NEGOCIO' && $f['deporte_id'] === null && $f['tipo'] === 'EGRESO')->sum('centavos'),
            'aportes' => $delMes->where('clasificacion', 'APORTE')->sum('centavos'),
            'retiros' => -$delMes->where('clasificacion', 'RETIRO')->sum('centavos'),
            'deuda' => $deuda, 'por_pagar' => $porPagar,
            'cajas' => $cajas,
            'disponible' => $cajas === null ? null : array_sum(array_column($cajas, 'total')),
            'historial_desde' => $hasta,
            // Disponible es del negocio entero: el saldo inicial no tiene deporte.
            'filas' => $delMesEconomico->filter($enDeporte)->values()->all(),
            'avisos' => $this->avisos(),
            'alumnos' => Alumno::where('activo', true)->when($deporteId, fn ($q) => $q->where('deporte_id', $deporteId))
                ->with(['deporte', 'grupo.nivel'])->get()->groupBy(fn ($a) => $a->deporte_id.':'.$a->grupo?->nivel_id)
                ->map(fn ($grupo) => ['deporte' => $grupo->first()->deporte?->nombre ?? 'Sin deporte',
                    'nivel' => $grupo->first()->grupo?->nivel?->nombre ?? 'Sin nivel', 'cantidad' => $grupo->count()])->values()->all(),
        ];
    }

    /**
     * Saldo de cada medio a una fecha: lo confirmado en Cashflow más lo que espera en cajas sin
     * validar, con el mismo signo que el reporte. Se suma en la base (T79): Cashflow lo muestra
     * sin calcular el reporte entero. Null antes del historial: los saldos iniciales antiguos no
     * tienen fecha efectiva y no se inventan cortes previos.
     */
    public function saldosPorCaja(string $fecha): ?array
    {
        if ($fecha < DB::table('reporte_cobertura')->where('id', 1)->value('desde')) return null;
        $iniciales = DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')->whereDate('fecha', '<=', $fecha)
            ->selectRaw('origen_id, SUM(delta_centavos) AS centavos')->groupBy('origen_id')->pluck('centavos', 'origen_id');
        $confirmados = DB::table('cashflow_movimientos')->whereDate('fecha', '<=', $fecha)
            ->selectRaw('tipo_caja_id, SUM(ROUND(monto * 100)) AS centavos')->groupBy('tipo_caja_id')->pluck('centavos', 'tipo_caja_id');
        $pendientes = DB::table('movimientos_operativos as m')
            ->join('cajas_operativas as c', 'c.id', '=', 'm.caja_operativa_id')
            ->leftJoin('subrubros as s', 's.id', '=', 'm.subrubro_id')->leftJoin('rubros as r', 'r.id', '=', 's.rubro_id')
            ->where('m.estado', 'ACTIVO')->where('c.estado', '!=', CajaOperativa::ESTADO_VALIDADA)->whereDate('m.fecha', '<=', $fecha)
            ->selectRaw("m.tipo_caja_id, SUM(CASE WHEN COALESCE(m.reporte_tipo, r.tipo) = 'EGRESO' THEN -ABS(ROUND(m.monto * 100)) ELSE ABS(ROUND(m.monto * 100)) END) AS centavos")
            ->groupBy('m.tipo_caja_id')->pluck('centavos', 'tipo_caja_id');
        return TipoCaja::orderBy('nombre')->get()->map(function ($tipo) use ($iniciales, $confirmados, $pendientes) {
            $confirmado = (int) ($iniciales[$tipo->id] ?? 0) + (int) ($confirmados[$tipo->id] ?? 0);
            $pendiente = (int) ($pendientes[$tipo->id] ?? 0);
            return ['id' => $tipo->id, 'nombre' => $tipo->abreviatura ?: $tipo->nombre,
                'confirmado' => $confirmado, 'pendiente' => $pendiente, 'total' => $confirmado + $pendiente];
        })->all();
    }

    public function evolucion(string $mes, ?int $deporteId = null): array
    {
        return $this->enLote(function () use ($mes, $deporteId) {
            $filas = $this->movimientos();
            $meses = $filas->map(fn ($f) => substr($f['fecha'], 0, 7))
                ->concat($this->filasEconomicas($filas)->pluck('periodo_ingreso'))->unique()
                ->filter(fn ($p) => $p < $mes && $p < today()->format('Y-m'))->sort()->take(-6);
            return $meses->map(fn ($p) => $this->obtener($p, $deporteId))->values()->all();
        });
    }

    /**
     * Lo que deben los alumnos: cuotas, inscripciones y punitorios de alumnos activos.
     *
     * T19 (Carlos, 10/10/2026): «Deuda real de los alumnos activos (cuotas + inscripciones)»,
     * la misma en Inicio, Reportes y Cobranza. Antes Reportes contaba solo cuotas y sumaba
     * también las de quienes ya se habían dado de baja.
     */
    private function deudaDeAlumnos(string $fecha, string $mes, ?int $deporteId): array
    {
        $cuotas = $this->saldoHistorico('CUOTA', $fecha, $mes, $deporteId);
        if (!$cuotas['disponible']) return $cuotas + ['cuotas' => null, 'inscripciones' => null, 'punitorios' => null];

        $inscripciones = $this->inscripcionesPendientes($fecha, $deporteId);
        $punitorios = $this->punitoriosPendientes($fecha, $mes, $deporteId);
        $filas = collect($cuotas['filas'])->concat($inscripciones)->concat($punitorios);

        return ['disponible' => true, 'total' => $filas->sum('centavos'), 'mes' => $filas->where('periodo', $mes)->sum('centavos'),
            'anteriores' => $filas->where('periodo', '<', $mes)->sum('centavos'), 'filas' => $filas->all(),
            'cuotas' => $cuotas['total'], 'inscripciones' => $inscripciones->sum('centavos'),
            'punitorios' => $punitorios->sum('centavos')];
    }

    /** El perdón se reconstruye a la fecha del corte desde su auditoría. */
    private function punitoriosPendientes(string $fecha, string $mes, ?int $deporteId): Collection
    {
        $cargos = CargoAlumno::where('tipo', 'PUNITORIO')->where('estado', 'VIGENTE')
            ->whereDate('created_at', '<=', $fecha)
            ->whereIn('alumno_id', Alumno::where('activo', true)
                ->when($deporteId, fn ($q) => $q->where('deporte_id', $deporteId))->select('id'))
            ->whereHas('deudaCuota', fn ($q) => $q->where('periodo', '<=', $mes))
            ->with(['deudaCuota', 'pagos' => fn ($q) => $q->whereDate('fecha_pago', '<=', $fecha)])->get();
        if ($cargos->isEmpty()) return collect();
        $eventos = DB::table('cargo_alumno_eventos')->whereIn('cargo_alumno_id', $cargos->pluck('id'))
            ->whereDate('created_at', '<=', $fecha)->orderBy('id')->get()->groupBy('cargo_alumno_id');
        // T39 (Carlos, 18/10): una anulación cuenta desde la fecha del cobro anulado, como su
        // asiento en Cashflow. Anular retira el pivot, así que ningún corte lo ve como pagado.
        $deportes = Alumno::whereIn('id', $cargos->pluck('alumno_id'))->pluck('deporte_id', 'id');
        return $cargos->map(function ($cargo) use ($eventos, $deportes) {
            $condonado = 0;
            foreach ($eventos->get($cargo->id, collect()) as $evento) {
                $detalle = json_decode($evento->detalle, true);
                if (in_array($evento->accion, ['CONDONAR', 'REACTIVAR'])) {
                    $condonado = (float) $detalle['monto_condonado_nuevo'];
                }
            }
            $pagado = (float) $cargo->pagos->sum('pivot.monto_aplicado');
            return ['id' => $cargo->id, 'persona_id' => $cargo->alumno_id,
                'periodo' => $cargo->deudaCuota->periodo, 'deporte_id' => (int) $deportes[$cargo->alumno_id],
                'centavos' => max(0, (int) round(((float) $cargo->monto_original - $condonado - $pagado) * 100)),
                'concepto' => $cargo->descripcion];
        })->filter(fn ($fila) => $fila['centavos'] > 0)->values();
    }

    /**
     * Inscripciones sin pagar a una fecha. Mismo criterio que Cobranza: cargo vigente de
     * una persona que tiene al menos un alumno activo. La inscripción es una por persona;
     * para filtrar por deporte se toma el del alumno con el que se generó.
     */
    private function inscripcionesPendientes(string $fecha, ?int $deporteId): Collection
    {
        $dnis = Alumno::where('activo', true)->pluck('dni')
            ->map(fn ($dni) => InscripcionService::dni($dni ?? ''))->filter()->unique();
        if ($dnis->isEmpty()) return collect();

        $cargos = CargoAlumno::where('tipo', 'INSCRIPCION')->where('estado', 'VIGENTE')
            ->whereIn('dni', $dnis)->whereDate('created_at', '<=', $fecha)->get();
        $pagado = DB::table('pago_cargo_alumno as pc')->join('pagos', 'pagos.id', '=', 'pc.pago_id')
            ->whereIn('pc.cargo_alumno_id', $cargos->pluck('id'))->whereDate('pagos.fecha_pago', '<=', $fecha)
            ->selectRaw('pc.cargo_alumno_id AS id, SUM(pc.monto_aplicado) AS pagado')->groupBy('pc.cargo_alumno_id')->pluck('pagado', 'id');
        $deportes = Alumno::whereIn('id', $cargos->pluck('alumno_id'))->pluck('deporte_id', 'id');

        return $cargos->map(fn ($cargo) => ['id' => $cargo->id, 'persona_id' => $cargo->alumno_id,
            'periodo' => $cargo->created_at->format('Y-m'), 'deporte_id' => isset($deportes[$cargo->alumno_id]) ? (int) $deportes[$cargo->alumno_id] : null,
            'centavos' => max(0, (int) round(((float) $cargo->monto_original - (float) $cargo->monto_condonado - (float) ($pagado[$cargo->id] ?? 0)) * 100)),
            'concepto' => 'Inscripción'])
            ->filter(fn ($fila) => $fila['centavos'] > 0 && ($deporteId === null || $fila['deporte_id'] === $deporteId))->values();
    }

    public function saldoHistorico(string $tipo, string $fecha, string $mes, ?int $deporteId = null): array
    {
        $desde = DB::table('reporte_cobertura')->where('id', 1)->value('desde');
        if (!$desde || $fecha < $desde) return ['disponible' => false, 'total' => null, 'mes' => null, 'anteriores' => null, 'filas' => []];
        $filas = DB::table('reporte_eventos')->where('tipo', $tipo)->whereDate('fecha', '<=', $fecha)
            ->where('periodo', '<=', $mes)->when($deporteId, fn ($q) => $q->where('deporte_id', $deporteId))
            // T19: quien se dio de baja no cuenta como «por cobrar».
            ->when($tipo === 'CUOTA', fn ($q) => $q->whereIn('persona_id', Alumno::where('activo', true)->select('id')))
            ->selectRaw('origen_id, persona_id, periodo, deporte_id, SUM(delta_centavos) AS centavos')
            ->groupBy('origen_id', 'persona_id', 'periodo', 'deporte_id')->havingRaw('SUM(delta_centavos) > 0')
            ->orderBy('periodo')->get()->map(fn ($f) => ['id' => $f->origen_id, 'persona_id' => $f->persona_id,
                'periodo' => $f->periodo, 'deporte_id' => $f->deporte_id, 'centavos' => (int) $f->centavos]);
        return ['disponible' => true, 'total' => $filas->sum('centavos'), 'mes' => $filas->where('periodo', $mes)->sum('centavos'),
            'anteriores' => $filas->where('periodo', '<', $mes)->sum('centavos'), 'filas' => $filas->all()];
    }

    private function movimientos(): Collection
    {
        if (!$this->enLote) return $this->leerMovimientos();
        return $this->movimientosDelLote ??= $this->leerMovimientos();
    }

    private function leerMovimientos(): Collection
    {
        // Cashflow es fuente confirmada; solo se agregan operativos todavía no reflejados.
        $cf = CashflowMovimiento::with('subrubro.rubro')->get();
        $pendientes = MovimientoOperativo::activos()->whereHas('cajaOperativa', fn ($q) => $q->where('estado', '!=', CajaOperativa::ESTADO_VALIDADA))
            ->with('subrubro.rubro')->get();
        $antiguos = $this->pagosDeAsientosAntiguos($cf);
        // T79: del movimiento de caja reflejado solo se usa el cobro, y del cobro solo lo que
        // lee PartesPagoCuotaService::partes(). Con dos años, armar los modelos enteros pesaba.
        $cobroDelOperativo = MovimientoOperativo::whereIn('id', $cf->pluck('movimiento_operativo_id')->filter()->unique())->pluck('pago_id', 'id');
        $idPago = fn ($m) => $m->referencia_tipo === CashflowMovimiento::REF_PAGO_CUOTA ? $m->referencia_id
            : ($cobroDelOperativo[$m->movimiento_operativo_id] ?? $antiguos[$m->id] ?? null);
        $pagos = Pago::select(['id', 'mes', 'anio', 'fecha_pago', 'estado', 'detalle_anulacion', 'monto_cuota', 'monto_final'])
            ->with(['deudasCuota' => fn ($q) => $q->select(['deuda_cuotas.id', 'deuda_cuotas.periodo'])])
            ->whereIn('id', $cf->toBase()->map($idPago)->concat($pendientes->pluck('pago_id'))->filter()->unique())->get()->keyBy('id');
        return $cf->toBase()->map(fn ($m) => $this->fila($m, true, $pagos->get($idPago($m))))
            ->concat($pendientes->map(fn ($m) => $this->fila($m, false, $pagos->get($m->pago_id))));
    }

    /** Recuperación de lectura, solo cuando la correspondencia financiera es uno a uno. */
    private function pagosDeAsientosAntiguos(Collection $cf): array
    {
        $viejos = $cf->filter(fn ($m) => $m->referencia_tipo === CashflowMovimiento::REF_CAJA
            && !$m->movimiento_operativo_id && $m->monto > 0 && $m->subrubro->nombre === 'Cuota Mensual');
        if ($viejos->isEmpty()) return [];
        $llave = fn ($m, $caja) => implode(':', [$caja, $m->fecha->toDateString(), $m->subrubro_id,
            $m->tipo_caja_id, (int) round((float) $m->monto * 100)]);
        $asientos = $cf->where('referencia_tipo', CashflowMovimiento::REF_CAJA)
            ->groupBy(fn ($m) => $llave($m, $m->referencia_id));
        $movimientos = MovimientoOperativo::activos()->whereIn('caja_operativa_id', $viejos->pluck('referencia_id'))
            ->get()->groupBy(fn ($m) => $llave($m, $m->caja_operativa_id));
        $resultado = [];
        foreach ($viejos as $m) {
            $clave = $llave($m, $m->referencia_id);
            $candidatos = $movimientos->get($clave, collect());
            if ($asientos[$clave]->count() === 1 && $candidatos->count() === 1 && $candidatos->first()->pago_id) {
                $resultado[$m->id] = $candidatos->first()->pago_id;
            }
        }
        return $resultado;
    }

    private function fila($m, bool $confirmado, ?Pago $pago = null): array
    {
        $tipo = $m->reporte_tipo ?? $m->subrubro->rubro->tipo;
        $centavos = (int) round((float) $m->monto * 100);
        if (!$confirmado) $centavos = $tipo === 'EGRESO' ? -abs($centavos) : abs($centavos);
        $partes = $pago && $m->subrubro->nombre === 'Cuota Mensual' && $tipo === 'INGRESO'
            ? app(PartesPagoCuotaService::class)->partes($pago) : [];
        // Sin mes pagado comprobable, Carlos conserva el mes original (20/10).
        $partes = array_values(array_filter($partes, fn ($p) => $p['periodo_cuota'] !== null));
        return ['id' => $m->id, 'origen' => $confirmado ? 'CASHFLOW' : 'OPERATIVO',
            'fecha' => $m->fecha->toDateString(), 'concepto' => $m->subrubro->nombre,
            'tipo_caja_id' => (int) $m->tipo_caja_id, 'tipo' => $tipo, 'centavos' => $centavos,
            'confirmado' => $confirmado, 'deporte_id' => $m->reporte_deporte_id === null ? null : (int) $m->reporte_deporte_id,
            'clasificacion' => $m->reporte_clasificacion, 'partes_cuota' => $partes];
    }

    /** Solo cambia la imputación del ingreso: fecha de cobro y filas financieras intactas. */
    private function filasEconomicas(Collection $filas): Collection
    {
        return $filas->flatMap(function ($fila) {
            if (!$fila['partes_cuota']) return [$fila + ['periodo_ingreso' => substr($fila['fecha'], 0, 7)]];
            return array_map(function ($parte) use ($fila) {
                return array_replace($fila, [
                    'periodo_ingreso' => $parte['periodo_comision'],
                    'centavos' => ($fila['centavos'] < 0 ? -1 : 1) * $parte['centavos'],
                    'concepto' => $fila['concepto'].' ('.substr($parte['periodo_cuota'], 5, 2).'/'.substr($parte['periodo_cuota'], 0, 4).')',
                ]);
            }, $fila['partes_cuota']);
        });
    }

    public function avisos(): array
    {
        // Consultas puras: no llamar resumenDiario(), que también manda avisos.
        return [
            'cajas' => CajaOperativa::where('estado', 'CERRADA')->count(),
            'liquidaciones' => Liquidacion::where('estado', 'CERRADA')->where('estado_pago', 'PENDIENTE')->count(),
            'liquidaciones_abiertas' => Liquidacion::where('estado', 'ABIERTA')->count(),
            'asistencia' => Clase::where('cancelada', false)->whereDate('fecha', '<', today())
                ->where('validada_para_liquidacion', false)->doesntHave('asistencias')->count(),
            'revision' => AlumnoRevisionCobranza::where('estado_revision', 'PENDIENTE')->count(),
        ];
    }
}
