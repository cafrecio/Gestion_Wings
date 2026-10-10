<?php

namespace App\Services;

use App\Models\{Alumno, AlumnoRevisionCobranza, CajaOperativa, CargoAlumno, CashflowMovimiento, Clase, Liquidacion, MovimientoOperativo, TipoCaja};
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReporteMensualService
{
    public function obtener(string $mes, ?int $deporteId = null): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $mes) || $mes > today()->format('Y-m')) {
            throw new InvalidArgumentException('Elegí un mes actual o anterior.');
        }
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes);
        $fin = min($inicio->endOfMonth()->toDateString(), today()->toDateString());
        $filas = $this->movimientos()->filter(fn ($f) => $f['fecha'] <= $fin);
        $delMes = $filas->filter(fn ($f) => substr($f['fecha'], 0, 7) === $mes);
        $enDeporte = fn ($f) => $deporteId === null || $f['deporte_id'] === $deporteId;
        $economicos = $delMes->filter(fn ($f) => $f['clasificacion'] === 'NEGOCIO' && $enDeporte($f));
        $ingresos = $economicos->where('tipo', 'INGRESO')->sum('centavos');
        $egresos = -$economicos->where('tipo', 'EGRESO')->sum('centavos');
        $desconocidos = $delMes->filter(fn ($f) => $f['clasificacion'] === null);
        $deuda = $this->deudaDeAlumnos($fin, $mes, $deporteId);
        $porPagar = $this->saldoHistorico('LIQUIDACION', $fin, $mes, $deporteId);
        $iniciales = DB::table('reporte_eventos')->where('tipo', 'CAJA_INICIAL')->whereDate('fecha', '<=', $fin)
            ->selectRaw('origen_id, SUM(delta_centavos) AS centavos')->groupBy('origen_id')->pluck('centavos', 'origen_id');
        $tipos = TipoCaja::orderBy('nombre')->get()->map(function ($tipo) use ($filas, $iniciales) {
            $propias = $filas->where('tipo_caja_id', $tipo->id);
            $confirmado = (int) ($iniciales[$tipo->id] ?? 0) + $propias->where('confirmado', true)->sum('centavos');
            $pendiente = $propias->where('confirmado', false)->sum('centavos');
            return ['id' => $tipo->id, 'nombre' => $tipo->abreviatura ?: $tipo->nombre,
                'confirmado' => $confirmado, 'pendiente' => $pendiente, 'total' => $confirmado + $pendiente];
        });
        $hasta = DB::table('reporte_cobertura')->where('id', 1)->value('desde');
        // Los saldos iniciales antiguos no tienen fecha efectiva: no inventar cortes previos.
        $saldoDisponible = $fin >= $hasta;
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
            'cajas' => $saldoDisponible ? $tipos->all() : null,
            'disponible' => $saldoDisponible ? $tipos->sum('total') : null,
            'historial_desde' => $hasta,
            // Disponible es del negocio entero: el saldo inicial no tiene deporte.
            'filas' => $delMes->filter($enDeporte)->values()->all(),
            'avisos' => $this->avisos(),
            'alumnos' => Alumno::where('activo', true)->when($deporteId, fn ($q) => $q->where('deporte_id', $deporteId))
                ->with(['deporte', 'grupo.nivel'])->get()->groupBy(fn ($a) => $a->deporte_id.':'.$a->grupo?->nivel_id)
                ->map(fn ($grupo) => ['deporte' => $grupo->first()->deporte?->nombre ?? 'Sin deporte',
                    'nivel' => $grupo->first()->grupo?->nivel?->nombre ?? 'Sin nivel', 'cantidad' => $grupo->count()])->values()->all(),
        ];
    }

    public function evolucion(string $mes, ?int $deporteId = null): array
    {
        $meses = $this->movimientos()->map(fn ($f) => substr($f['fecha'], 0, 7))->unique()
            ->filter(fn ($p) => $p < $mes && $p < today()->format('Y-m'))->sort()->take(-6);
        return $meses->map(fn ($p) => $this->obtener($p, $deporteId))->values()->all();
    }

    /**
     * Lo que deben los alumnos: cuotas más inscripciones, solo de alumnos activos.
     *
     * T19 (Carlos, 10/10/2026): «Deuda real de los alumnos activos (cuotas + inscripciones)»,
     * la misma en Inicio, Reportes y Cobranza. Antes Reportes contaba solo cuotas y sumaba
     * también las de quienes ya se habían dado de baja.
     */
    private function deudaDeAlumnos(string $fecha, string $mes, ?int $deporteId): array
    {
        $cuotas = $this->saldoHistorico('CUOTA', $fecha, $mes, $deporteId);
        if (!$cuotas['disponible']) return $cuotas + ['cuotas' => null, 'inscripciones' => null];

        $inscripciones = $this->inscripcionesPendientes($fecha, $deporteId);
        $filas = collect($cuotas['filas'])->concat($inscripciones);

        return ['disponible' => true, 'total' => $filas->sum('centavos'), 'mes' => $filas->where('periodo', $mes)->sum('centavos'),
            'anteriores' => $filas->where('periodo', '<', $mes)->sum('centavos'), 'filas' => $filas->all(),
            'cuotas' => $cuotas['total'], 'inscripciones' => $inscripciones->sum('centavos')];
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
        // Cashflow es fuente confirmada; solo se agregan operativos todavía no reflejados.
        $cf = CashflowMovimiento::with('subrubro.rubro')->get()->map(fn ($m) => $this->fila($m, true));
        $pendientes = MovimientoOperativo::activos()->whereHas('cajaOperativa', fn ($q) => $q->where('estado', '!=', CajaOperativa::ESTADO_VALIDADA))
            ->with('subrubro.rubro')->get()->map(fn ($m) => $this->fila($m, false));
        return $cf->concat($pendientes);
    }

    private function fila($m, bool $confirmado): array
    {
        $tipo = $m->reporte_tipo ?? $m->subrubro->rubro->tipo;
        $centavos = (int) round((float) $m->monto * 100);
        if (!$confirmado) $centavos = $tipo === 'EGRESO' ? -abs($centavos) : abs($centavos);
        return ['id' => $m->id, 'origen' => $confirmado ? 'CASHFLOW' : 'OPERATIVO',
            'fecha' => $m->fecha->toDateString(), 'concepto' => $m->subrubro->nombre,
            'tipo_caja_id' => (int) $m->tipo_caja_id, 'tipo' => $tipo, 'centavos' => $centavos,
            'confirmado' => $confirmado, 'deporte_id' => $m->reporte_deporte_id === null ? null : (int) $m->reporte_deporte_id,
            'clasificacion' => $m->reporte_clasificacion];
    }

    public function avisos(): array
    {
        // Consultas puras: no llamar resumenDiario(), que también manda avisos.
        return [
            'cajas' => CajaOperativa::where('estado', 'CERRADA')->count(),
            'liquidaciones' => Liquidacion::where('estado', 'CERRADA')->where('estado_pago', 'PENDIENTE')->count(),
            'liquidaciones_abiertas' => Liquidacion::where('estado', 'ABIERTA')->count(),
            'asistencia' => Clase::where('cancelada', false)->whereDate('fecha', '<', today())
                ->where('validada_para_liquidacion', false)->whereDoesntHave('asistencias', fn ($q) => $q->where('presente', true))->count(),
            'revision' => AlumnoRevisionCobranza::where('estado_revision', 'PENDIENTE')->count(),
        ];
    }
}
