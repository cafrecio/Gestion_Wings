<?php

namespace Tests\Support;

use App\Models\{CajaOperativa, CashflowMovimiento, Clase, Configuracion, DeudaCuota, Liquidacion, LiquidacionDetalle, MovimientoOperativo, Pago};
use Illuminate\Support\Facades\DB;

/** Escenario ficticio de presentación; solo se permite la base descartable de Codex. */
final class ImportesT30
{
    public ImportesT24 $f;
    public Liquidacion $hora;
    public Pago $pago;
    public MovimientoOperativo $movimiento;
    public MovimientoOperativo $cobro;
    public string $importe;
    public string $totalMovimientos;
    public string $totalHora;

    public function __construct(string $importe = '1250.50', bool $sinCaja = false)
    {
        if (!in_array(config('database.connections.mysql.database'), ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T30 solo permite bases descartables de pruebas, nunca la base del club.');
        }
        // Las migraciones pueden haberse ejecutado antes bajo otro reloj de test.
        // Este escenario conoce octubre completo; no depende del orden de la suite.
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2026-10-01']);
        DB::table('reporte_analitico_cobertura')->where('id', 1)->update(['desde' => '2026-10-01 00:00:00.000000']);
        $this->importe = $importe;
        $this->totalMovimientos = number_format((float) $importe + 250.50, 2, '.', '');
        $this->totalHora = number_format((float) $importe * 1.5, 2, '.', '');
        $this->f = $f = new ImportesT24($sinCaja ? 'apertura' : 't30');
        $f->admin->update(['name' => 'Administración T30']);
        $f->operativo->update(['name' => 'Mostrador T30']);
        $f->alumno->update(['apellido' => 'Prueba T30']);
        $f->medio->update(['saldo_inicial' => $importe]);
        $f->plan->update(['precio_mensual' => $importe]);
        $f->profesor->update(['valor_hora' => $importe]);
        Configuracion::set('inscripcion_importe', $importe);
        DeudaCuota::where('alumno_id', $f->alumno->id)->get()->each(fn ($deuda) => $deuda->update(['monto_original' => $importe]));
        foreach ([$importe, '250.50'] as $valor) {
            CashflowMovimiento::create(['fecha' => today(), 'subrubro_id' => $f->ingreso->id,
                'tipo_caja_id' => $f->medio->id, 'monto' => $valor, 'observaciones' => 'Interés ficticio T30',
                'usuario_admin_id' => $f->admin->id]);
            if ($f->caja) {
                $movimiento = MovimientoOperativo::create(['caja_operativa_id' => $f->caja->id,
                    'fecha' => today(), 'tipo_caja_id' => $f->medio->id, 'subrubro_id' => $f->ingreso->id,
                    'monto' => $valor, 'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO',
                    'observaciones' => 'Interés ficticio T30']);
                if (!isset($this->movimiento)) $this->movimiento = $movimiento;
            }
        }
        if ($f->caja) $f->caja->update(['efectivo_inicial' => $importe]);
        $this->pago = Pago::create(['alumno_id' => $f->alumno->id, 'plan_id' => $f->plan->id,
            'mes' => 9, 'anio' => 2026, 'monto_base' => $importe, 'porcentaje_aplicado' => 100,
            'monto_final' => $importe, 'monto_cuota' => $importe, 'fecha_pago' => today(),
            'estado' => 'COMPLETADO']);
        $deudaPagada = DeudaCuota::create(['alumno_id' => $f->alumno->id, 'periodo' => '2026-09',
            'monto_original' => $importe, 'monto_pagado' => $importe, 'estado' => 'PAGADA']);
        $this->pago->deudasCuota()->attach($deudaPagada->id, ['monto_aplicado' => $importe]);
        if ($f->caja) {
            $this->cobro = MovimientoOperativo::create(['caja_operativa_id' => $f->caja->id,
                'fecha' => today(), 'tipo_caja_id' => $f->medio->id,
                'subrubro_id' => \App\Models\Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail()->id,
                'monto' => $importe, 'alumno_id' => $f->alumno->id, 'pago_id' => $this->pago->id,
                'usuario_id' => $f->operativo->id, 'estado' => 'ACTIVO', 'observaciones' => 'Cobro ficticio T30']);
        }
        $f->liquidacion->update(['mes' => 9, 'total_calculado' => number_format((float) $importe * .2, 2, '.', '')]);
        LiquidacionDetalle::create(['liquidacion_id' => $f->liquidacion->id, 'tipo_referencia' => 'alumno',
            'referencia_id' => $f->alumno->id, 'monto' => number_format((float) $importe * .2, 2, '.', ''),
            'descripcion' => 'Comisión ficticia - Pago $'.number_format((float) $importe, 2, ',', '.').' (20%)']);
        $this->hora = Liquidacion::create(['profesor_id' => $f->profesor->id, 'mes' => 10, 'anio' => 2026,
            'tipo' => 'HORA', 'valor_hora_aplicado' => $importe, 'total_calculado' => $this->totalHora,
            'estado' => 'ABIERTA', 'estado_pago' => 'PENDIENTE']);
        foreach ([60, 30] as $i => $minutos) {
            $clase = Clase::create(['grupo_id' => $f->grupo->id, 'fecha' => '2026-10-0'.($i + 1),
                'hora_inicio' => '10:00', 'hora_fin' => $minutos === 60 ? '11:00' : '10:30',
                'validada_para_liquidacion' => true]);
            $clase->profesores()->attach($f->profesor->id);
            LiquidacionDetalle::create(['liquidacion_id' => $this->hora->id, 'tipo_referencia' => 'clase',
                'referencia_id' => $clase->id, 'minutos' => $minutos,
                'monto' => number_format((float) $importe * $minutos / 60, 2, '.', ''),
                'descripcion' => 'Clase ficticia T30']);
        }
    }

    /** Rutas reales: [url, muestra, papel, campos de entrada ya correctos por T24]. */
    public function pantallas(): array
    {
        $f = $this->f;
        $a = $f->alumno->id; $g = $f->grupo->id; $p = $f->profesor->id; $m = $f->medio->id;
        $c = $f->caja?->id;
        return [
            'admin' => ['/admin/dashboard', $this->totalMovimientos, 'ADMIN', false],
            'operativo' => ['/operativo', $this->totalMovimientos, 'OPERATIVO', false],
            'alumno-alta' => ['/alumnos/create', $this->importe, 'ADMIN', true],
            'alumno-editar' => ["/alumnos/$a/edit", $this->importe, 'ADMIN', true],
            'alumno-ficha' => ["/alumnos/$a", $this->importe, 'ADMIN', false],
            'caja' => ['/caja', number_format((float) $this->totalMovimientos + (float) $this->importe, 2, '.', ''), 'ADMIN', false],
            'caja-rectificar' => ["/caja/$c/rectificar", $this->importe, 'ADMIN', false],
            'caja-cerrar' => ["/caja/$c/cerrar", $this->importe, 'ADMIN', false],
            'caja-resumen' => ["/caja/$c/resumen", number_format((float) $this->totalMovimientos + (float) $this->importe, 2, '.', ''), 'ADMIN', false],
            'caja-detalle' => ["/caja/$c/detalle", $this->importe, 'ADMIN', false],
            'caja-historial' => ['/caja/historial', $this->importe, 'ADMIN', false],
            'cobrar-buscador' => ['/caja/cobrar', $this->importe, 'ADMIN', false],
            'cobrar' => ["/caja/cobrar/$a", $this->importe, 'ADMIN', false],
            'caja-agregar' => ["/caja/$c/editar", $this->importe, 'ADMIN', true],
            'caja-editar' => ["/caja/$c/movimientos/{$this->movimiento->id}/editar", $this->importe, 'ADMIN', true],
            'caja-movimiento' => ['/caja/movimiento', $this->importe, 'OPERATIVO', true],
            'caja-cancelar' => ["/caja/$c/movimientos/{$this->cobro->id}/cancelar", $this->importe, 'ADMIN', false],
            'cashflow' => ['/cashflow', $this->totalMovimientos, 'ADMIN', false],
            'movimientos' => ['/movimientos', number_format((float) $this->importe * 2 + 250.50, 2, '.', ''), 'ADMIN', false],
            'cashflow-movimiento' => ['/cashflow/movimiento', $this->importe, 'ADMIN', true],
            'cobranza' => ['/cobranza', $this->importe, 'ADMIN', false],
            'grupos' => ['/grupos', $this->importe, 'ADMIN', false],
            'grupo-ficha' => ["/grupos/$g", $this->importe, 'ADMIN', false],
            'grupo-alta' => ['/grupos/create', $this->importe, 'ADMIN', true],
            'grupo-editar' => ["/grupos/$g/edit", $this->importe, 'ADMIN', true],
            'profesores' => ['/profesores', $this->importe, 'ADMIN', false],
            'profesor-ficha' => ["/profesores/$p", $this->importe, 'ADMIN', false],
            'profesor-alta' => ['/profesores/create', $this->importe, 'ADMIN', true],
            'profesor-editar' => ["/profesores/$p/edit", $this->importe, 'ADMIN', true],
            'medio-alta' => ['/tipos-caja/create', $this->importe, 'ADMIN', true],
            'medio-editar' => ["/tipos-caja/$m/edit", $this->importe, 'ADMIN', true],
            'configuracion' => ['/configuraciones', $this->importe, 'ADMIN', true],
            'liquidaciones' => ['/liquidaciones', $this->totalHora, 'ADMIN', false],
            'liquidacion-hora' => ["/liquidaciones/{$this->hora->id}", $this->importe, 'ADMIN', false],
            'liquidacion-comision' => ["/liquidaciones/{$f->liquidacion->id}", $this->importe, 'ADMIN', false],
            'reportes' => ['/reportes', $this->totalMovimientos, 'ADMIN', false],
            'sueldos' => ['/reportes/sueldos', $this->totalHora, 'ADMIN', false],
        ];
    }

    public function importesGuardados(): array
    {
        $estado = [];
        foreach ([\App\Models\TipoCaja::class, MovimientoOperativo::class, CashflowMovimiento::class,
            DeudaCuota::class, Pago::class, \App\Models\GrupoPlan::class, \App\Models\Profesor::class,
            Liquidacion::class, LiquidacionDetalle::class] as $modelo) {
            $tabla = (new $modelo)->getTable();
            $estado[$tabla] = DB::table($tabla)->orderBy('id')->get()->map(fn ($fila) => (array) $fila)->all();
        }
        return $estado;
    }
}
