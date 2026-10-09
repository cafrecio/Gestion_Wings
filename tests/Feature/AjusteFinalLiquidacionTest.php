<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Deporte, Liquidacion, LiquidacionDetalle, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{HistorialAnaliticoReportesService, LiquidacionPagoService, LiquidacionService};
use Carbon\{Carbon, CarbonImmutable};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AjusteFinalLiquidacionTest extends TestCase
{
    use RefreshDatabase;
    private User $admin;
    private Profesor $profesor;
    private TipoCaja $caja;
    private Subrubro $subrubro;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 18:00:00');
        DB::table('reporte_analitico_cobertura')->where('id', 1)->update(['desde'=>'2026-10-01 00:00:00']);
        DB::table('reporte_cobertura')->where('id', 1)->update(['desde'=>'2026-10-01']);
        DB::table('primera_carga')->where('id', 1)->update(['estado'=>'TERMINADA']);
        $this->admin = User::factory()->create(['rol'=>'ADMIN','activo'=>true]);
        $d = Deporte::create(['nombre'=>'Deporte de prueba','tipo_liquidacion'=>'COMISION','activo'=>true]);
        $this->profesor = Profesor::create(['deporte_id'=>$d->id,'nombre'=>'Docente','apellido'=>'Prueba','dni'=>'31000001',
            'fecha_nacimiento'=>'1980-01-01','direccion'=>'Prueba','localidad'=>'Prueba','porcentaje_comision'=>20,'activo'=>true]);
        $r = Rubro::create(['nombre'=>'Sueldos prueba','tipo'=>'EGRESO']);
        $this->subrubro = Subrubro::create(['rubro_id'=>$r->id,'nombre'=>'Sueldo prueba','permitido_para'=>'ADMIN','afecta_caja'=>false,'activo'=>true]);
        $this->profesor->update(['subrubro_id'=>$this->subrubro->id]);
        $this->caja = TipoCaja::create(['nombre'=>'Banco prueba','saldo_inicial'=>500000,'activo'=>true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function liquidacion(string $estado = 'CERRADA', string $tipo = 'COMISION'): Liquidacion
    {
        $l = Liquidacion::create(['profesor_id'=>$this->profesor->id,'mes'=>10,'anio'=>2026,'tipo'=>$tipo,
            'total_calculado'=>20000,'porcentaje_comision_aplicado'=>20,'estado'=>'ABIERTA','estado_pago'=>'PENDIENTE']);
        LiquidacionDetalle::create(['liquidacion_id'=>$l->id,'tipo_referencia'=>'ALUMNO','referencia_id'=>1,'monto'=>20000,'descripcion'=>'Detalle de prueba']);
        if ($estado !== 'ABIERTA') $l->update(['estado'=>$estado]);
        return $l;
    }

    private function datosPago(?string $esperado = null): array
    {
        return ['fecha_pago'=>'2026-10-09','tipo_caja_id'=>$this->caja->id,'subrubro_id'=>$this->subrubro->id,
            'admin_id'=>$this->admin->id,'monto_esperado'=>$esperado];
    }

    public function test_admin_ajusta_abierta_y_cerrada_sin_cambiar_calculo_o_detalle(): void
    {
        foreach (['ABIERTA','CERRADA'] as $estado) {
            $l = $this->liquidacion($estado);
            $antes = $l->detalles->toJson();
            $this->actingAs($this->admin)->post(route('web.liquidaciones.ajustar', $l->id),
                ['monto_final'=>'25000.25','monto_anterior'=>'20000','motivo'=>'Acuerdo del mes'])
                ->assertRedirect(route('web.liquidaciones.show', $l->id))->assertSessionHas('success');
            $l = $l->fresh('detalles');
            $this->assertSame('20000.00', $l->total_calculado);
            $this->assertSame('25000.25', $l->monto_a_pagar);
            $this->assertSame($estado, $l->estado);
            $this->assertSame($antes, $l->detalles->toJson());
            $this->assertDatabaseHas('liquidacion_ajustes',['liquidacion_id'=>$l->id,'admin_id'=>$this->admin->id,'anterior'=>20000,'nuevo'=>25000.25]);
        }
        $this->assertEquals(50000.50, app(LiquidacionService::class)->obtenerResumenPeriodo(10,2026)['total_monto']);
    }

    public function test_operativo_profesor_inactivo_y_anonimo_no_ajustan(): void
    {
        $l = $this->liquidacion();
        $datos = ['monto_final'=>'30000','monto_anterior'=>'20000','motivo'=>'Acuerdo del mes'];
        $this->post(route('web.liquidaciones.ajustar',$l->id), $datos)->assertRedirect(route('login'));
        foreach (['OPERATIVO','PROFESOR'] as $rol) {
            $u = User::factory()->create(['rol'=>$rol,'activo'=>true]);
            $this->actingAs($u)->post(route('web.liquidaciones.ajustar',$l->id),$datos)->assertForbidden();
            try { $l->ajustarMontoFinal('30000',$u->id,'Acuerdo del mes'); $this->fail('Debió rechazar el rol.'); }
            catch (\Illuminate\Auth\Access\AuthorizationException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->admin->forceFill(['activo'=>false])->save();
        try { $l->ajustarMontoFinal('30000',$this->admin->id,'Acuerdo del mes'); $this->fail('Debió rechazar inactivo.'); }
        catch (\Illuminate\Auth\Access\AuthorizationException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->assertDatabaseCount('liquidacion_ajustes',0);
    }

    public function test_pagada_cancelada_y_por_hora_rechazan_ajustes(): void
    {
        foreach (['PAGADA','CANCELADA','HORA'] as $caso) {
            $l = $this->liquidacion('CERRADA',$caso === 'HORA' ? 'HORA' : 'COMISION');
            if ($caso === 'PAGADA') $l->update(['estado_pago'=>'PAGADA']);
            if ($caso === 'CANCELADA') app(LiquidacionService::class)->cancelarLiquidacion($l->id,'Cancelación de prueba',$this->admin->id);
            $this->actingAs($this->admin)->post(route('web.liquidaciones.ajustar',$l->id),
                ['monto_final'=>'30000','monto_anterior'=>'20000','motivo'=>'Acuerdo del mes'])->assertSessionHas('error');
            $this->assertNull($l->fresh()->monto_final);
        }
        $this->assertDatabaseCount('liquidacion_ajustes',0);
    }

    public function test_pago_usa_final_una_sola_vez_y_rechaza_formulario_viejo(): void
    {
        $l = $this->liquidacion();
        $l->ajustarMontoFinal('30000.25',$this->admin->id,'Acuerdo del mes');
        $s = new LiquidacionPagoService();
        try { $s->marcarComoPagada($l->id,$this->datosPago('20000')); $this->fail('Debió rechazar importe viejo.'); }
        catch (\Exception $e) { $this->assertStringContainsString('importe cambió',$e->getMessage()); }
        $r = $s->marcarComoPagada($l->id,$this->datosPago('30000.25'));
        $this->assertSame('-30000.25',$r['cashflow_movimiento']->monto);
        $this->assertTrue($s->marcarComoPagada($l->id,$this->datosPago('30000.25'))['ya_pagada']);
        $this->assertSame(1,CashflowMovimiento::where('referencia_tipo','LIQUIDACION')->where('referencia_id',$l->id)->count());
        $this->assertSame('20000.00',$l->fresh()->total_calculado);
    }

    public function test_ajuste_conserva_cortes_anteriores_y_saldo_pendiente_coherente(): void
    {
        $l = $this->liquidacion();
        Carbon::setTestNow('2026-10-10 18:00:00');
        $l->ajustarMontoFinal('30000',$this->admin->id,'Acuerdo del mes');
        $s = app(HistorialAnaliticoReportesService::class);
        $antes = $s->obtener('LIQUIDACION',CarbonImmutable::parse('2026-10-09 23:59:59'),$l->id)['filas'][0]['datos'];
        $nuevo = $s->obtener('LIQUIDACION',CarbonImmutable::parse('2026-10-10 23:59:59'),$l->id)['filas'][0]['datos'];
        $this->assertSame(2000000,$antes['final_centavos']);
        $this->assertSame(3000000,$nuevo['final_centavos']);
        $this->assertSame(2000000,$nuevo['calculado_centavos']);
        $this->assertEquals(3000000,DB::table('reporte_eventos')->where('tipo','LIQUIDACION')->where('origen_id',$l->id)->sum('delta_centavos'));
    }

    public function test_falla_historial_revierte_importe_y_auditoria(): void
    {
        $l = $this->liquidacion();
        $mock = \Mockery::mock(HistorialAnaliticoReportesService::class);
        $mock->shouldReceive('registrar')->once()->andThrow(new \RuntimeException('Falla de prueba'));
        $this->app->instance(HistorialAnaliticoReportesService::class,$mock);
        try { $l->ajustarMontoFinal('30000',$this->admin->id,'Acuerdo del mes'); $this->fail('Debió revertir.'); }
        catch (\RuntimeException $e) { $this->assertSame('Falla de prueba',$e->getMessage()); }
        $this->assertNull($l->fresh()->monto_final);
        $this->assertDatabaseCount('liquidacion_ajustes',0);
        $this->assertEquals(2000000,DB::table('reporte_eventos')->where('tipo','LIQUIDACION')->where('origen_id',$l->id)->sum('delta_centavos'));
    }

    public function test_guardado_directo_sin_auditoria_y_edicion_vieja_rechazados(): void
    {
        $l = $this->liquidacion();
        $l->monto_final = 30000;
        try { $l->save(); $this->fail('Debió rechazar guardado directo.'); }
        catch (\Exception $e) { $this->assertStringContainsString('registro del cambio',$e->getMessage()); }
        $l = $l->fresh();
        $l->ajustarMontoFinal('25000',$this->admin->id,'Acuerdo del mes','20000');
        try { $l->ajustarMontoFinal('30000',$this->admin->id,'Acuerdo del mes','20000'); $this->fail('Debió rechazar edición vieja.'); }
        catch (\Exception $e) { $this->assertStringContainsString('importe cambió',$e->getMessage()); }
        $this->assertDatabaseCount('liquidacion_ajustes',1);
    }
}
