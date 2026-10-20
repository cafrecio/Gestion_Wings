<?php

namespace Tests\Feature;

use App\Models\{Alumno, Asistencia, CashflowMovimiento, Clase, Deporte, DeudaCuota, Grupo, Liquidacion, Nivel, Pago, Profesor, Rubro, Subrubro, TipoCaja, User};
use App\Services\{LiquidacionPagoService, LiquidacionService, ReciboService};
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DecisionComisionAtrasadaT43Test extends TestCase
{
    use RefreshDatabase;

    private Alumno $alumno;
    private Profesor $profesor;
    private User $admin;
    private LiquidacionService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2027-02-05 12:00:00');
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $deporte = Deporte::create(['nombre' => 'Comisión T43', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $nivel = Nivel::create(['nombre' => 'Nivel T43']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $this->profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Docente', 'apellido' => 'T43',
            'dni' => '32123457', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba',
            'porcentaje_comision' => 40, 'activo' => true]);
        $this->alumno = Alumno::create(['deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'nombre' => 'Alumno', 'apellido' => 'T43', 'dni' => '45123457', 'fecha_nacimiento' => '2010-01-01',
            'celular' => '1100000043', 'fecha_alta' => '2026-01-01', 'activo' => true]);
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

    private function pago(array $partes, string $fecha = '2026-10-15'): Pago
    {
        $monto = array_sum($partes);
        $pago = Pago::create(['alumno_id' => $this->alumno->id, 'mes' => (int) substr($fecha, 5, 2),
            'anio' => (int) substr($fecha, 0, 4), 'fecha_pago' => $fecha, 'monto_base' => $monto,
            'porcentaje_aplicado' => 100, 'monto_final' => $monto, 'monto_cuota' => $monto, 'estado' => 'COMPLETADO']);
        foreach ($partes as $periodo => $importe) {
            $deuda = DeudaCuota::firstOrCreate(['alumno_id' => $this->alumno->id, 'periodo' => $periodo], [
                'monto_original' => $importe, 'monto_pagado' => $importe, 'estado' => 'PAGADA']);
            $pago->deudasCuota()->attach($deuda->id, ['monto_aplicado' => $importe]);
        }
        return $pago;
    }

    private function liquidar(array $partes = ['2026-09' => 100, '2026-10' => 200]): Liquidacion
    {
        $this->presente('2026-10');
        $this->pago($partes);
        return $this->servicio->generarLiquidacionMensual($this->profesor->id, 10, 2026);
    }

    private function decidir(Liquidacion $liq, string $decision, string $esperada = 'PENDIENTE'): Liquidacion
    {
        $d = $liq->detalles()->where('origen_cuota', 'atrasado')->firstOrFail();
        return $this->servicio->decidirAtrasada($liq->id, $d->id, $decision, $this->admin->id, $esperada);
    }

    public function test_cobro_mixto_persiste_tres_origenes_y_separa_adelantada(): void
    {
        $liq = $this->liquidar(['2026-09' => 100, '2026-10' => 200, '2026-11' => 300]);
        $this->presente('2026-11');
        $nov = $this->servicio->generarLiquidacionMensual($this->profesor->id, 11, 2026);
        $this->assertSame('120.00', $liq->total_calculado);
        $this->assertSame('80.00', $liq->monto_a_pagar);
        $this->assertSame('40.00', $liq->total_atrasadas_excluidas);
        $this->assertSame(['atrasado', 'del_mes'], $liq->detalles->pluck('origen_cuota')->all());
        $this->assertSame('PENDIENTE', $liq->detalles[0]->decision_atrasada);
        $this->assertSame('100.00', $liq->detalles[0]->base_cuota);
        $this->assertSame('2026-10-15', $liq->detalles[0]->fecha_cobro->toDateString());
        $this->assertNotNull($liq->detalles[0]->pago_id);
        $this->assertNotNull($liq->detalles[0]->deuda_cuota_id);
        $this->assertSame('adelantado', $nov->detalles->sole()->origen_cuota);
        $this->assertNull($nov->detalles->sole()->decision_atrasada);
        $this->assertSame('120.00', $nov->monto_a_pagar);
    }

    public function test_pendiente_impide_cierre_incluso_desde_modelo(): void
    {
        $liq = $this->liquidar();
        foreach ([fn () => $this->servicio->cerrarLiquidacion($liq->id), fn () => $liq->update(['estado' => 'CERRADA'])] as $cerrar) {
            try { $cerrar(); $this->fail('No debe cerrar sin decidir.'); }
            catch (\Exception $e) { $this->assertStringContainsString('cuota atrasada', $e->getMessage()); }
        }
        $this->assertSame('ABIERTA', $liq->fresh()->estado);
    }

    public function test_validar_rechazar_y_reenviar_conservan_calculo_y_auditoria_sin_mover_caja(): void
    {
        $liq = $this->liquidar();
        $antes = CashflowMovimiento::count();
        $liq = $this->decidir($liq, 'APROBADA');
        $this->assertSame('120.00', $liq->monto_a_pagar);
        $corteAprobado = DB::table('reporte_analitico_historial')->where('tipo', 'LIQUIDACION')->where('origen_id', $liq->id)->orderByDesc('id')->first();
        $this->assertSame(12000, json_decode($corteAprobado->datos, true)['final_centavos']);
        $this->assertSame($this->admin->id, $liq->detalles[0]->decision_por);
        $this->assertNotNull($liq->detalles[0]->decision_at);
        $this->decidir($liq, 'APROBADA');
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
        $liq = $this->decidir($liq, 'RECHAZADA', 'APROBADA');
        $this->assertSame('120.00', $liq->total_calculado);
        $this->assertSame('80.00', $liq->monto_a_pagar);
        $actual = json_decode(DB::table('reporte_analitico_historial')->where('tipo', 'LIQUIDACION')->where('origen_id', $liq->id)->orderByDesc('id')->value('datos'), true);
        $this->assertSame(8000, $actual['final_centavos']);
        $this->assertSame(4000, $actual['atrasadas_excluidas_centavos']);
        $this->assertSame($corteAprobado->datos, DB::table('reporte_analitico_historial')->where('id', $corteAprobado->id)->value('datos'));
        $this->assertDatabaseHas('liquidacion_decisiones_atrasadas', ['liquidacion_id' => $liq->id,
            'anterior' => 'APROBADA', 'nueva' => 'RECHAZADA', 'admin_id' => $this->admin->id, 'base_cuota' => 100, 'comision' => 40]);
        $this->assertSame($antes, CashflowMovimiento::count());
    }

    public function test_formulario_viejo_no_pisa_decision_nueva(): void
    {
        $liq = $this->decidir($this->liquidar(), 'APROBADA');
        try { $this->decidir($liq, 'RECHAZADA'); $this->fail('Debe detectar cambio.'); }
        catch (ValidationException $e) { $this->assertStringContainsString('Recargá', $e->getMessage()); }
        $this->assertSame('APROBADA', $liq->detalles()->where('origen_cuota', 'atrasado')->first()->decision_atrasada);
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
    }

    public function test_recalculo_conserva_decision_equivalente_y_auditoria(): void
    {
        $liq = $this->decidir($this->liquidar(), 'APROBADA');
        $anterior = $liq->detalles()->where('origen_cuota', 'atrasado')->first();
        $liq = $this->servicio->recalcularLiquidacion($liq->id);
        $d = $liq->detalles()->where('origen_cuota', 'atrasado')->first();
        $this->assertNotSame($anterior->id, $d->id);
        $this->assertSame('APROBADA', $d->decision_atrasada);
        $this->assertEquals($anterior->decision_at, $d->decision_at);
        $this->assertSame('120.00', $liq->monto_a_pagar);
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
        $this->servicio->eliminarLiquidacion($liq->id);
        $this->assertDatabaseHas('liquidacion_decisiones_atrasadas', ['detalle_id' => $anterior->id]);
    }

    public function test_cambio_de_base_exige_nueva_decision_sin_borrar_la_anterior(): void
    {
        $liq = $this->decidir($this->liquidar(), 'APROBADA');
        $d = $liq->detalles()->where('origen_cuota', 'atrasado')->first();
        DB::table('pago_deuda_cuota')->where('pago_id', $d->pago_id)->where('deuda_cuota_id', $d->deuda_cuota_id)->update(['monto_aplicado' => 50]);
        $liq = $this->servicio->recalcularLiquidacion($liq->id);
        $this->assertTrue($liq->tieneAtrasadasPendientes());
        $this->assertSame('100.00', $liq->total_calculado);
        $this->assertSame('80.00', $liq->monto_a_pagar);
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
    }

    public function test_anulado_sale_de_abierta_pero_cerrada_y_pagada_no_cambian(): void
    {
        $liq = $this->decidir($this->liquidar(), 'APROBADA');
        $pago = Pago::findOrFail($liq->detalles[0]->pago_id);
        $this->servicio->cerrarLiquidacion($liq->id);
        $liq->refresh()->update(['estado_pago' => 'PAGADA']);
        $antes = $liq->fresh('detalles')->toArray();
        $pago->update(['estado' => 'ANULADO']);
        try { $this->servicio->recalcularLiquidacion($liq->id); $this->fail('Pagada inmutable.'); }
        catch (\Exception $e) { $this->assertStringContainsString('cerrada', $e->getMessage()); }
        $this->assertSame($antes, $liq->fresh('detalles')->toArray());
        $this->presente('2026-11');
        $p = $this->pago(['2026-10' => 100], '2026-11-15');
        $abierta = $this->servicio->generarLiquidacionMensual($this->profesor->id, 11, 2026);
        $this->assertTrue($abierta->tieneAtrasadasPendientes());
        $p->update(['estado' => 'ANULADO']);
        $this->assertSame('0.00', $this->servicio->recalcularLiquidacion($abierta->id)->monto_a_pagar);
    }

    public function test_cambio_de_anio_atrasada_en_enero_adelantada_en_febrero(): void
    {
        foreach (['2027-01', '2027-02'] as $p) $this->presente($p);
        $this->pago(['2026-12' => 100, '2027-01' => 200, '2027-02' => 300], '2027-01-15');
        $ene = $this->servicio->generarLiquidacionMensual($this->profesor->id, 1, 2027);
        $feb = $this->servicio->generarLiquidacionMensual($this->profesor->id, 2, 2027);
        $this->assertSame('80.00', $ene->monto_a_pagar);
        $this->assertTrue($ene->tieneAtrasadasPendientes());
        $this->assertSame('120.00', $feb->monto_a_pagar);
        $this->assertFalse($feb->tieneAtrasadasPendientes());
    }

    public function test_redondeo_por_pago_se_conserva_al_partir_centavos(): void
    {
        $liq = $this->liquidar(['2026-09' => 0.01, '2026-10' => 0.01]);
        $this->assertSame('0.01', $liq->total_calculado);
        $this->assertEquals(0.01, $liq->detalles->sum('monto'));
        $this->decidir($liq, 'APROBADA');
        $this->assertSame('0.01', $this->servicio->recalcularLiquidacion($liq->id)->monto_a_pagar);
    }

    public function test_historico_sin_imputacion_no_inventa_origen_ni_decision(): void
    {
        $this->presente('2026-10');
        Pago::create(['alumno_id' => $this->alumno->id, 'mes' => 10, 'anio' => 2026,
            'fecha_pago' => '2026-10-15', 'monto_base' => 100, 'monto_final' => 100,
            'monto_cuota' => 100, 'porcentaje_aplicado' => 100, 'estado' => 'COMPLETADO']);
        $liq = $this->servicio->generarLiquidacionMensual($this->profesor->id, 10, 2026);
        $this->assertSame('40.00', $liq->monto_a_pagar);
        $this->assertNull($liq->detalles->sole()->origen_cuota);
        $this->assertFalse($liq->tieneAtrasadasPendientes());
        $this->servicio->cerrarLiquidacion($liq->id);
        $this->assertSame('CERRADA', $liq->fresh()->estado);
    }

    public function test_http_roles_y_detalle_ajeno_no_pueden_decidir(): void
    {
        $liq = $this->liquidar();
        $d = $liq->detalles->first();
        $ruta = route('web.liquidaciones.decidir-atrasada', [$liq->id, $d->id]);
        $datos = ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE'];
        $this->post($ruta, $datos)->assertRedirect(route('login'));
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $u = User::factory()->create(['rol' => $rol, 'activo' => true]);
            $this->actingAs($u)->post($ruta, $datos)->assertForbidden();
            try { $this->servicio->decidirAtrasada($liq->id, $d->id, 'APROBADA', $u->id, 'PENDIENTE'); $this->fail('Rol inválido.'); }
            catch (\Illuminate\Auth\Access\AuthorizationException $e) { $this->assertInstanceOf(\Exception::class, $e); }
        }
        $this->actingAs($this->admin)->postJson(route('web.liquidaciones.decidir-atrasada', [$liq->id + 999, $d->id]), $datos)->assertNotFound();
        $this->actingAs($this->admin)->postJson($ruta, ['decision' => 'OTRA', 'decision_esperada' => 'PENDIENTE'])->assertUnprocessable();
        $this->actingAs($this->admin)->post($ruta, $datos)->assertRedirect(route('web.liquidaciones.show', $liq->id));
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
        $this->get(route('web.liquidaciones.show', $liq->id))->assertOk()->assertSee('Revisión completa')->assertSee('Validada');
    }

    public function test_ajuste_final_absoluto_se_conserva_y_recibo_excluye_rechazada(): void
    {
        Storage::fake();
        $liq = $this->liquidar();
        $liq->ajustarMontoFinal('150.00', $this->admin->id, 'Acuerdo del mes');
        $liq = $this->decidir($liq, 'RECHAZADA');
        $this->assertSame('150.00', $liq->monto_a_pagar);
        $this->assertSame('80.00', $liq->total_confirmado);
        $this->servicio->cerrarLiquidacion($liq->id);
        $liq->refresh()->update(['estado_pago' => 'PAGADA']);
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('A5', 'portrait');
        $pdf->shouldReceive('output')->once()->andReturn('PDF de prueba');
        Pdf::shouldReceive('loadView')->once()->with('pdfs.recibo-liquidacion', \Mockery::on(function ($data) {
            $this->assertCount(1, $data['detalles']);
            $this->assertSame('2026-10', $data['detalles'][0]['periodo_cuota']);
            $this->assertEquals(80, $data['total_calculado']);
            $this->assertEquals(70, $data['ajuste_final']);
            $this->assertEquals(150, $data['monto_total']);
            $html = view('pdfs.recibo-liquidacion', $data)->render();
            $this->assertStringContainsString('Cuota 10/2026', $html);
            $this->assertStringNotContainsString('Cuota 09/2026', $html);
            return true;
        }))->andReturn($pdf);
        app(ReciboService::class)->generarReciboLiquidacion($liq->id, true);
    }

    public function test_cerrada_pagada_y_cuota_del_mes_rechazan_decisiones(): void
    {
        $liq = $this->liquidar();
        $normal = $liq->detalles()->where('origen_cuota', 'del_mes')->firstOrFail();
        $datos = ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE'];
        $this->actingAs($this->admin)->postJson(route('web.liquidaciones.decidir-atrasada', [$liq->id, $normal->id]), $datos)->assertUnprocessable();
        $liq = $this->decidir($liq, 'RECHAZADA');
        $atrasada = $liq->detalles()->where('origen_cuota', 'atrasado')->firstOrFail();
        $this->servicio->cerrarLiquidacion($liq->id);
        foreach (['PENDIENTE', 'PAGADA'] as $estadoPago) {
            $liq->refresh()->update(['estado_pago' => $estadoPago]);
            $this->actingAs($this->admin)->postJson(route('web.liquidaciones.decidir-atrasada', [$liq->id, $atrasada->id]),
                ['decision' => 'APROBADA', 'decision_esperada' => 'RECHAZADA'])->assertUnprocessable();
        }
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
        $this->assertSame('80.00', $liq->fresh()->monto_a_pagar);
    }

    public function test_detalle_de_otra_liquidacion_e_inactivo_no_escriben_auditoria(): void
    {
        $liq = $this->liquidar();
        $this->presente('2026-11');
        $this->pago(['2026-11' => 100], '2026-11-15');
        $otra = $this->servicio->generarLiquidacionMensual($this->profesor->id, 11, 2026);
        $this->actingAs($this->admin)->postJson(route('web.liquidaciones.decidir-atrasada', [$liq->id, $otra->detalles->sole()->id]),
            ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE'])->assertNotFound();
        $this->admin->forceFill(['activo' => false])->save();
        try { $this->decidir($liq, 'APROBADA'); $this->fail('Inactivo no autorizado.'); }
        catch (\Illuminate\Auth\Access\AuthorizationException $e) { $this->assertInstanceOf(\Exception::class, $e); }
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 0);
        $this->assertTrue($liq->tieneAtrasadasPendientes());
    }

    public function test_pago_y_saldo_por_pagar_usan_solo_comisiones_confirmadas(): void
    {
        $liq = $this->decidir($this->liquidar(), 'RECHAZADA');
        $rubro = Rubro::create(['nombre' => 'Sueldos T43', 'tipo' => 'EGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Docente T43', 'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'activo' => true]);
        $this->profesor->update(['subrubro_id' => $sub->id]);
        $caja = TipoCaja::create(['nombre' => 'Banco T43', 'saldo_inicial' => 1000, 'activo' => true]);
        $this->servicio->cerrarLiquidacion($liq->id);
        $datos = ['fecha_pago' => '2027-02-05', 'tipo_caja_id' => $caja->id, 'subrubro_id' => $sub->id, 'admin_id' => $this->admin->id, 'monto_esperado' => '80.00'];
        $this->assertEquals(8000, DB::table('reporte_eventos')->where('tipo', 'LIQUIDACION')->where('origen_id', $liq->id)->sum('delta_centavos'));
        $pago = app(LiquidacionPagoService::class)->marcarComoPagada($liq->id, $datos);
        $this->assertSame('-80.00', $pago['cashflow_movimiento']->monto);
        $this->assertTrue(app(LiquidacionPagoService::class)->marcarComoPagada($liq->id, $datos)['ya_pagada']);
        $this->assertSame(1, CashflowMovimiento::where('referencia_tipo', 'LIQUIDACION')->where('referencia_id', $liq->id)->count());
        $this->assertSame('120.00', $liq->fresh()->total_calculado);
        $this->assertEquals(0, DB::table('reporte_eventos')->where('tipo', 'LIQUIDACION')->where('origen_id', $liq->id)->sum('delta_centavos'));
    }
    public function test_decision_json_actualiza_importes_y_cierre_sin_consultas_de_pantalla(): void
    {
        $liq = $this->liquidar();
        $d = $liq->detalles->firstWhere('origen_cuota', 'atrasado');
        $ruta = route('web.liquidaciones.decidir-atrasada', [$liq->id, $d->id]);
        $this->actingAs($this->admin);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $respuesta = $this->postJson($ruta, ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE']);
        $consultas = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();
        $respuesta->assertOk()->assertJsonPath('detalle_id', $d->id)
            ->assertJsonPath('decision', 'APROBADA')->assertJsonPath('estado', 'Validada')
            ->assertJsonPath('importes.confirmado', '$120.00')->assertJsonPath('importes.final', '$120.00')
            ->assertJsonPath('importes.ajuste', '$0.00');
        $this->assertStringContainsString('Cerrar', $respuesta->json('acciones'));
        $this->assertStringContainsString('Revisión completa', $respuesta->json('revision'));
        // El endpoint no vuelve a buscar alumnos, grupos, asistencia, cobros ni cuentas.
        foreach (['alumnos', 'grupos', 'asistencias', 'pagos', 'tipos_caja', 'deudas_cuota'] as $tabla) {
            $this->assertDoesNotMatchRegularExpression('/(?:from|join) [`"]?'.preg_quote($tabla, '/').'[`"]?(?:\s|$)/i', $consultas);
        }
        $this->postJson($ruta, ['decision' => 'RECHAZADA', 'decision_esperada' => 'APROBADA'])
            ->assertOk()->assertJsonPath('importes.confirmado', '$80.00')->assertJsonPath('estado', 'Rechazada');
        $this->assertSame('80.00', $liq->fresh()->total_confirmado);
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 2);
        $this->get(route('web.liquidaciones.show', $liq->id))->assertOk()->assertSee('Total confirmado')
            ->assertSee('data-liquidacion-importe="confirmado"', false);
    }

    public function test_json_idempotente_y_pestana_desactualizada_no_pisan_decision(): void
    {
        $liq = $this->liquidar();
        $d = $liq->detalles->firstWhere('origen_cuota', 'atrasado');
        $ruta = route('web.liquidaciones.decidir-atrasada', [$liq->id, $d->id]);
        $this->actingAs($this->admin);
        $datos = ['decision' => 'APROBADA', 'decision_esperada' => 'PENDIENTE'];
        $this->postJson($ruta, $datos)->assertOk();
        $this->postJson($ruta, $datos)->assertOk()->assertJsonPath('importes.final', '$120.00');
        $this->postJson($ruta, ['decision' => 'RECHAZADA', 'decision_esperada' => 'PENDIENTE'])
            ->assertUnprocessable()->assertJsonValidationErrors('decision');
        $this->assertDatabaseCount('liquidacion_decisiones_atrasadas', 1);
        $this->assertSame('APROBADA', $d->fresh()->decision_atrasada);
    }

}
