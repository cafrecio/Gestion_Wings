<?php

namespace Tests\Feature;

use App\Models\{Alumno, AlumnoPlan, CajaOperativa, DeudaCuota, Deporte, Grupo, GrupoPlan, MovimientoOperativo, Nivel, Pago, Subrubro, TipoCaja, User};
use App\Services\{CajaService, PagoCuotaService};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification};
use Tests\TestCase;

class T88B3DineroTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operativa;
    protected Alumno $alumno;
    protected CajaOperativa $caja;
    protected TipoCaja $efectivo;
    protected Subrubro $gasto;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-27 12:00:00');
        Notification::fake();
        $this->withoutVite();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => 'OPERATIVO', 'activo' => true]);
        $this->efectivo = TipoCaja::firstOrFail();
        $this->caja = \Tests\Support\CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => 100]);
        $this->gasto = Subrubro::where('es_reservado_sistema', false)->where('afecta_caja', true)
            ->where('permitido_para', 'OPERATIVO')->whereHas('rubro', fn ($q) => $q->where('tipo', 'EGRESO'))->firstOrFail();
        $grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id, 'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
        $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
        $this->alumno = Alumno::create(['nombre' => 'Alumno', 'apellido' => 'Excepcion', 'dni' => '31888123',
            'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'fecha_alta' => '2026-01-01',
            'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $this->alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-01-01', 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function deuda(string $periodo): DeudaCuota
    {
        return DeudaCuota::create(['alumno_id' => $this->alumno->id, 'periodo' => $periodo,
            'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }

    protected function datosGasto(float $monto, ?int $medio = null): array
    {
        return ['usuario_operativo_id' => $this->operativa->id, 'tipo_caja_id' => $medio ?? $this->efectivo->id,
            'subrubro_id' => $this->gasto->id, 'monto' => $monto, 'fecha' => '2026-10-27', 'observaciones' => 'Prueba gasto T88'];
    }

    protected function cobro(array $montos, array $extra = []): array
    {
        return $extra + ['tipo_caja_id' => $this->efectivo->id, 'periodos' => array_keys($montos),
            'montos_cuota' => $montos, 'fecha_pago' => '2026-10-27'];
    }

    public function test_rechaza_gasto_que_supera_el_cajon_sin_escribir(): void
    {
        $this->actingAs($this->operativa)->post(route('web.caja.movimiento.store'), $this->datosGasto(100.01))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'aporte'));
        $this->assertDatabaseCount('movimientos_operativos', 0);
        $this->assertSame('100.00', app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
    }

    public function test_el_gasto_exacto_se_permite_y_una_transferencia_no_usa_el_cajon(): void
    {
        $otro = TipoCaja::create(['nombre' => 'Transferencia T88', 'activo' => true]);
        app(CajaService::class)->registrarMovimientoOperativo($this->datosGasto(50000, $otro->id));
        app(CajaService::class)->registrarMovimientoOperativo($this->datosGasto(100));
        $this->assertSame('0.00', app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
        $this->assertDatabaseCount('movimientos_operativos', 2);
    }

    public function test_editar_no_puede_dejar_negativo_ni_cambiar_el_registro(): void
    {
        $m = app(CajaService::class)->registrarMovimientoOperativo($this->datosGasto(50));
        $antes = $m->fresh()->getRawOriginal();
        $this->actingAs($this->operativa)->put(route('web.caja.movimientos.update', [$this->caja->id, $m->id]), $this->datosGasto(101))
            ->assertSessionHas('error', fn ($s) => str_contains($s, 'aporte'));
        $this->assertSame($antes, $m->fresh()->getRawOriginal());
    }

    public function test_editar_descuenta_la_version_anterior_y_admite_agotar_el_cajon(): void
    {
        $m = app(CajaService::class)->registrarMovimientoOperativo($this->datosGasto(50));
        app(CajaService::class)->actualizarMovimientoEnCaja($this->caja->id, $m->id, $this->datosGasto(100));
        $this->assertSame('0.00', app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado']);
    }

    public function test_tambien_se_valida_el_alta_en_caja_existente_por_servicio(): void
    {
        try {
            app(CajaService::class)->registrarMovimientoEnCaja($this->caja->id, $this->datosGasto(101));
            $this->fail('Se acepto gasto sin efectivo.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('aporte', $e->getMessage());
        }
        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    public function test_cambiar_un_gasto_de_transferencia_a_efectivo_no_evade_el_saldo(): void
    {
        $otro = TipoCaja::create(['nombre' => 'Transferencia cambio T88', 'activo' => true]);
        $m = app(CajaService::class)->registrarMovimientoOperativo($this->datosGasto(101, $otro->id));
        $antes = $m->fresh()->getRawOriginal();
        try {
            app(CajaService::class)->actualizarMovimientoEnCaja($this->caja->id, $m->id, $this->datosGasto(101));
            $this->fail('Cambiar de medio eludio el efectivo disponible.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('aporte', $e->getMessage());
        }
        $this->assertSame($antes, $m->fresh()->getRawOriginal());
    }

    public function test_un_parcial_sin_motivo_pide_excepcion_y_no_escribe(): void
    {
        $d = $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id), $this->cobro(['2026-10' => 10000]))
            ->assertStatus(409)->assertJsonPath('requiere_confirmacion', true)->assertJsonPath('requiere_motivo', true);
        $this->assertSame('0.00', $d->fresh()->monto_pagado);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('movimientos_operativos', 0);
    }

    public function test_parcial_con_motivo_guarda_excepcion_atomica_sin_aviso_individual(): void
    {
        $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 10000], ['motivo' => 'Completa al cobrar el sueldo']))->assertRedirect();
        $p = Pago::sole();
        $this->assertDatabaseHas('excepciones_cobranza', ['tipo' => 'COBRO_PARCIAL', 'alumno_id' => $this->alumno->id,
            'autor_id' => $this->operativa->id, 'motivo' => 'Completa al cobrar el sueldo', 'registro_tipo' => 'PAGO', 'registro_id' => $p->id]);
        Notification::assertNothingSent();
    }

    public function test_el_duenio_desde_la_ficha_tambien_necesita_motivo_y_lo_registra(): void
    {
        $d = $this->deuda('2026-10');
        $this->actingAs($this->admin)->postJson(route('web.caja.pagar', $this->alumno->id), $this->cobro(['2026-10' => 10000]))->assertStatus(409);
        $this->assertSame('0.00', $d->fresh()->monto_pagado);
        $this->postJson(route('web.caja.pagar', $this->alumno->id), $this->cobro(['2026-10' => 10000], ['motivo' => 'Acuerdo del duenio']))->assertRedirect();
        $this->assertDatabaseHas('excepciones_cobranza', ['autor_id' => $this->admin->id, 'tipo' => 'COBRO_PARCIAL']);
        $this->assertDatabaseMissing('cajas_operativas', ['usuario_operativo_id' => $this->admin->id]);
    }

    public function test_avisa_tambien_un_mes_impago_entre_dos_seleccionados(): void
    {
        foreach (['2026-03', '2026-05', '2026-10'] as $p) $this->deuda($p);
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-03' => 28000, '2026-10' => 28000]))->assertStatus(409)
            ->assertJsonPath('cantidad_meses', 1)->assertJsonPath('periodos.0', '2026-05')->assertJsonPath('monto_total', 28000);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_pagar_todos_los_anteriores_no_pide_excepcion(): void
    {
        foreach (['2026-03', '2026-05', '2026-10'] as $p) $this->deuda($p);
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-03' => 28000, '2026-05' => 28000, '2026-10' => 28000]))->assertRedirect();
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_si_es_parcial_y_salta_deuda_registra_ambos_tipos_una_vez(): void
    {
        $this->deuda('2026-05'); $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 10000], ['confirmar_deuda_anterior' => 1, 'motivo' => 'Acuerdo con la familia']))->assertRedirect();
        $this->assertSame(['COBRO_DEUDA_ANTERIOR', 'COBRO_PARCIAL'], DB::table('excepciones_cobranza')->orderBy('tipo')->pluck('tipo')->all());
        Notification::assertNothingSent();
    }

    public function test_el_servicio_directo_tampoco_permite_omitir_el_motivo(): void
    {
        $this->deuda('2026-10');
        try {
            app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
                'usuario_admin_id' => $this->admin->id, 'tipo_caja_id' => $this->efectivo->id,
                'items' => [['periodo' => '2026-10', 'monto' => 10000]]]);
            $this->fail('Se acepto parcial sin motivo por servicio.');
        } catch (\App\Exceptions\ExcepcionCobranzaRequerida $e) {
            $this->assertStringContainsString('parcial', $e->getMessage());
        }
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_el_importe_entregado_se_distribuye_antes_de_detectar_el_parcial(): void
    {
        $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 28000], ['monto_entregado' => 10000]))->assertStatus(409)->assertJsonPath('requiere_motivo', true);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_espacios_no_son_un_motivo_y_no_dejan_excepcion_ni_pago(): void
    {
        $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 10000], ['motivo' => '   ', 'confirmar_deuda_anterior' => 1]))->assertStatus(422);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('excepciones_cobranza', 0);
    }

    public function test_el_duenio_ve_la_excepcion_y_su_motivo_en_inicio(): void
    {
        $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 10000], ['motivo' => 'Completa en noviembre']))->assertRedirect();
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Excepciones justificadas')->assertSee('Completa en noviembre')->assertSee('Excepcion, Alumno');
        $this->assertDatabaseCount('excepciones_cobranza', 1);
    }

    public function test_se_incluyen_juntas_en_un_resumen_por_canal_sin_avisos_individuales(): void
    {
        $this->deuda('2026-05'); $this->deuda('2026-10');
        $this->actingAs($this->operativa)->postJson(route('web.caja.pagar', $this->alumno->id),
            $this->cobro(['2026-10' => 10000], ['motivo' => 'Acuerdo familiar', 'confirmar_deuda_anterior' => 1]))->assertRedirect();
        Notification::assertNothingSent();
        $this->assertTrue(app(\App\Services\AvisoAdminService::class)->resumenDiario());
        Notification::assertSentTo($this->admin, \App\Notifications\AvisoOperativo::class, function ($aviso) {
            $datos = $aviso->toArray($this->admin)['datos'];
            return isset($datos['Excepciones justificadas (últimas 24 horas)'])
                && str_contains($datos['Excepciones justificadas (últimas 24 horas)'], '2');
        });
        Notification::assertSentToTimes($this->admin, \App\Notifications\AvisoOperativo::class, 1);
        Notification::assertSentOnDemandTimes(\App\Notifications\AvisoOperativo::class, 1);
    }

    public function test_si_falla_el_movimiento_tambien_revierte_pago_deuda_y_excepcion(): void
    {
        $d = $this->deuda('2026-10');
        try {
            app(PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $this->alumno->id,
                'usuario_admin_id' => $this->admin->id, 'tipo_caja_id' => 999999999,
                'items' => [['periodo' => '2026-10', 'monto' => 10000]], 'motivo' => 'Parcial autorizado que fallara al persistir']);
            $this->fail('El movimiento con medio inexistente debia fallar.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('foreign key', strtolower($e->getMessage()));
        }
        $this->assertSame('0.00', $d->fresh()->monto_pagado);
        foreach (['pagos', 'pago_deuda_cuota', 'excepciones_cobranza', 'cashflow_movimientos'] as $tabla) $this->assertDatabaseCount($tabla, 0);
    }
}
