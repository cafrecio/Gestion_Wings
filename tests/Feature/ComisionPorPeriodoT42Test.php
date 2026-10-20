<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, Clase, Deporte, DeudaCuota, Grupo, Liquidacion, Nivel, Pago, Profesor};
use App\Services\{LiquidacionService, PartesPagoCuotaService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComisionPorPeriodoT42Test extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;
    private Profesor $profesor;
    private LiquidacionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2027-02-05 12:00:00');
        $deporte = Deporte::create(['nombre' => 'Comisión T42', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T42']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Prueba', 'apellido' => 'T42',
            'dni' => '32123456', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Prueba 42', 'localidad' => 'Prueba',
            'porcentaje_comision' => 40, 'activo' => true]);
        $this->alumno = Alumno::create(['deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'nombre' => 'Alumno', 'apellido' => 'T42', 'dni' => '45123456', 'fecha_nacimiento' => '2010-01-01',
            'celular' => '1100000042', 'fecha_alta' => '2026-01-01', 'activo' => true]);
        $this->servicio = app(LiquidacionService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function presente(string $periodo): void
    {
        $clase = Clase::create(['grupo_id' => $this->alumno->grupo_id, 'fecha' => $periodo.'-10',
            'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00', 'cancelada' => false]);
        $clase->profesores()->attach($this->profesor->id);
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $this->alumno->id, 'presente' => true]);
    }

    private function pago(array $imputaciones, string $fecha = '2026-10-15', float $extra = 0): Pago
    {
        $cuota = array_sum($imputaciones);
        $pago = Pago::create(['alumno_id' => $this->alumno->id, 'mes' => (int) substr($fecha, 5, 2),
            'anio' => (int) substr($fecha, 0, 4), 'fecha_pago' => $fecha, 'monto_base' => $cuota,
            'porcentaje_aplicado' => 100, 'monto_final' => $cuota + $extra, 'monto_cuota' => $cuota,
            'estado' => Pago::ESTADO_COMPLETADO]);
        foreach ($imputaciones as $periodo => $monto) {
            $deuda = DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo,
                'monto_original' => $monto, 'monto_pagado' => $monto, 'estado' => 'PAGADA']);
            $pago->deudasCuota()->attach($deuda->id, ['monto_aplicado' => $monto]);
        }
        return $pago;
    }

    private function liquidar(string $periodo, float $esperado): Liquidacion
    {
        [$anio, $mes] = array_map('intval', explode('-', $periodo));
        $preview = $this->servicio->previsualizarLiquidacion($this->profesor->id, $mes, $anio);
        $this->assertEquals($esperado, $preview['total_estimado']);
        $liq = $this->servicio->generarLiquidacionMensual($this->profesor->id, $mes, $anio);
        $this->assertEquals($esperado, $liq->total_calculado);
        $this->assertEquals($esperado, $liq->detalles->sum('monto'));
        return $liq;
    }

    public function test_adelantado_se_liquida_en_el_mes_pagado_y_no_en_el_cobro(): void
    {
        $this->presente('2026-10');
        $this->presente('2026-11');
        $this->pago(['2026-11' => 100]);
        $this->liquidar('2026-10', 0);
        $this->liquidar('2026-11', 40);
    }

    public function test_cuota_del_mes_y_atrasada_siguen_en_el_mes_del_cobro(): void
    {
        $this->presente('2026-09');
        $this->presente('2026-10');
        $pago = $this->pago(['2026-09' => 100, '2026-10' => 200]);
        $this->liquidar('2026-09', 0);
        $this->liquidar('2026-10', 120);
        $partes = app(PartesPagoCuotaService::class)->partes($pago);
        $this->assertSame(['atrasado', 'del_mes'], array_column($partes, 'origen'));
    }

    public function test_un_cobro_mixto_se_reparte_sin_duplicar_su_importe(): void
    {
        foreach (['2026-09', '2026-10', '2026-11'] as $mes) $this->presente($mes);
        $pago = $this->pago(['2026-09' => 100, '2026-10' => 200, '2026-11' => 300]);
        $this->liquidar('2026-09', 0);
        $this->liquidar('2026-10', 120);
        $this->liquidar('2026-11', 120);
        $this->assertSame(60000, array_sum(array_column(app(PartesPagoCuotaService::class)->partes($pago), 'centavos')));
    }

    public function test_adelantado_exige_asistencia_en_el_mes_de_liquidacion(): void
    {
        $this->presente('2026-10');
        $this->pago(['2026-11' => 100]);
        $liq = $this->liquidar('2026-11', 0);
        $this->presente('2026-11');
        $this->assertEquals(40, $this->servicio->recalcularLiquidacion($liq->id)->total_calculado);
    }

    public function test_cambio_de_anio_lleva_diciembre_adelantado_a_enero(): void
    {
        $this->presente('2026-12');
        $this->presente('2027-01');
        $this->pago(['2027-01' => 100], '2026-12-15');
        $this->liquidar('2026-12', 0);
        $this->liquidar('2027-01', 40);
    }

    public function test_anulado_no_aporta_comision_ni_al_cobro_ni_al_mes_pagado(): void
    {
        $this->presente('2026-10');
        $this->presente('2026-11');
        $pago = $this->pago(['2026-10' => 100, '2026-11' => 100]);
        $liq = $this->liquidar('2026-11', 40);
        $pago->update(['estado' => Pago::ESTADO_ANULADO]);
        $this->assertEquals(0, $this->servicio->recalcularLiquidacion($liq->id)->total_calculado);
        $this->liquidar('2026-10', 0);
    }

    public function test_sin_imputaciones_conserva_mes_anio_historicos_aunque_difiera_la_fecha(): void
    {
        $this->presente('2026-09');
        $this->presente('2026-10');
        Pago::create(['alumno_id' => $this->alumno->id, 'mes' => 9, 'anio' => 2026,
            'fecha_pago' => '2026-10-15', 'monto_base' => 100, 'porcentaje_aplicado' => 100,
            'monto_final' => 100, 'estado' => Pago::ESTADO_COMPLETADO]);
        $this->liquidar('2026-09', 40);
        $this->liquidar('2026-10', 0);
    }

    public function test_cerrada_y_pagada_conservan_cabecera_y_detalles_tras_anular_el_pago(): void
    {
        $this->presente('2026-10');
        $this->presente('2026-11');
        $pago = $this->pago(['2026-10' => 100, '2026-11' => 100]);
        $cerrada = $this->liquidar('2026-10', 40);
        $pagada = $this->liquidar('2026-11', 40);
        $this->servicio->cerrarLiquidacion($cerrada->id);
        $this->servicio->cerrarLiquidacion($pagada->id);
        $pagada->refresh()->update(['estado_pago' => 'PAGADA']);
        $antes = [$cerrada->fresh()->toArray(), $pagada->fresh()->toArray(),
            $cerrada->detalles()->get()->toArray(), $pagada->detalles()->get()->toArray()];
        $pago->update(['estado' => Pago::ESTADO_ANULADO]);
        foreach ([$cerrada, $pagada] as $liq) {
            try {
                $this->servicio->recalcularLiquidacion($liq->id);
                $this->fail('Debe rechazar el recálculo de un documento cerrado.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('cerrada', $e->getMessage());
            }
        }
        $this->assertSame($antes, [$cerrada->fresh()->toArray(), $pagada->fresh()->toArray(),
            $cerrada->detalles()->get()->toArray(), $pagada->detalles()->get()->toArray()]);
    }

    public function test_centavos_se_suman_por_pago_y_mes_antes_de_redondear_comision(): void
    {
        $this->presente('2026-10');
        $this->pago(['2026-09' => 0.01, '2026-10' => 0.01, '2026-11' => 0.01]);
        $this->liquidar('2026-10', 0.01);
    }

    public function test_solo_las_cuotas_imputadas_forman_la_base_sin_inscripcion(): void
    {
        $this->presente('2026-11');
        $this->pago(['2026-11' => 100], extra: 50);
        $this->liquidar('2026-11', 40);
    }
}
