<?php

namespace Tests\Feature;

use App\Models\{CashflowMovimiento, Liquidacion, Rubro, Subrubro, User};
use App\Services\{CashflowService, ReporteMensualService};
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Notification, Schema, Storage};
use Illuminate\Support\Str;
use Tests\Support\ImportesT24;
use Tests\TestCase;

class CashflowCorreccionesT27Test extends TestCase
{
    use RefreshDatabase;
    private ImportesT24 $f;

    protected function beforeRefreshingDatabase(): void
    {
        if (!app()->environment('testing') || !in_array(config('database.connections.mysql.database'),
            ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
            throw new \RuntimeException('T27 solo permite bases descartables de pruebas.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-12 12:00:00');
        Notification::fake(); Storage::fake();
        $this->f = new ImportesT24('apertura');
        $this->actingAs($this->f->admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function directo(string $tipo = 'INGRESO', string $clasificacion = 'NEGOCIO', string $fecha = '2026-10-12'): CashflowMovimiento
    {
        $rubro = Rubro::firstOrCreate(['nombre' => 'Movimiento T27 '.$tipo], ['tipo' => $tipo]);
        $sub = Subrubro::firstOrCreate(['nombre' => 'Concepto T27 '.$tipo.' '.$clasificacion], ['rubro_id' => $rubro->id, 'permitido_para' => 'ADMIN',
            'activo' => true, 'clasificacion_resultado' => $clasificacion, 'afecta_caja' => false]);
        return app(CashflowService::class)->registrarMovimientoAdmin(['usuario_admin_id' => $this->f->admin->id,
            'tipo_caja_id' => $this->f->medio->id, 'subrubro_id' => $sub->id, 'monto' => '48.50',
            'fecha' => $fecha, 'observaciones' => 'Original T27']);
    }

    private function datos(CashflowMovimiento $mov, array $cambios = []): array
    {
        return array_replace(['clave' => (string) Str::uuid(), 'version' => (int) $mov->version_correccion,
            'fecha' => $mov->fecha->toDateString(), 'tipo_caja_id' => $mov->tipo_caja_id,
            'subrubro_id' => $mov->subrubro_id, 'monto' => '75.25', 'observaciones' => 'Corregido T27'], $cambios);
    }

    private function ruta(CashflowMovimiento $mov): string { return '/cashflow/movimientos/'.$mov->id; }

    private function foto(): array
    {
        return collect(['cashflow_movimientos', 'cashflow_correcciones', 'pagos', 'deuda_cuotas', 'liquidaciones'])
            ->filter(fn ($tabla) => Schema::hasTable($tabla))
            ->mapWithKeys(fn ($tabla) => [$tabla => DB::table($tabla)->orderBy('id')->get()->toJson()])->all();
    }

    public function test_editar_conserva_id_creador_y_antes_despues_con_centavos_y_motivo_opcional(): void
    {
        $mov = $this->directo('EGRESO');
        $otroAdmin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $this->actingAs($otroAdmin);
        $this->get($this->ruta($mov).'/editar')->assertOk()->assertSee('48.50');
        $this->putJson($this->ruta($mov), $this->datos($mov))->assertRedirect();
        $this->assertSame('-75.25', $mov->fresh()->monto);
        $this->assertSame($this->f->admin->id, $mov->fresh()->usuario_admin_id);
        $this->assertSame(1, CashflowMovimiento::count());
        $historia = DB::table('cashflow_correcciones')->sole();
        $this->assertSame($otroAdmin->id, $historia->admin_id);
        $this->assertNull($historia->motivo);
        $this->assertSame('-48.50', json_decode($historia->antes, true)['monto']);
        $this->assertSame('-75.25', json_decode($historia->despues, true)['monto']);
        $this->get($this->ruta($mov))->assertOk()->assertSee('Original T27')->assertSee('Corregido T27');
    }

    public function test_pedido_directo_rechaza_tres_decimales_cero_negativos_letras_y_fuera_de_capacidad(): void
    {
        $mov = $this->directo(); $antes = $this->foto();
        foreach (['48.505', '48.000', '0', '-48.50', 'abc', '10000000000.00'] as $monto) {
            $this->putJson($this->ruta($mov), $this->datos($mov, ['monto' => $monto]))
                ->assertUnprocessable()->assertJsonValidationErrors('monto');
            $this->assertSame($antes, $this->foto());
        }
    }

    public function test_cualquier_mes_anterior_se_corrige_y_no_admite_fecha_futura(): void
    {
        $mov = $this->directo('INGRESO', 'NEGOCIO', '2020-01-02');
        $this->putJson($this->ruta($mov), $this->datos($mov))->assertRedirect();
        $this->assertSame('2020-01-02', $mov->fresh()->fecha->toDateString());
        $antes = $this->foto();
        $this->putJson($this->ruta($mov), $this->datos($mov->fresh(), ['fecha' => '2026-10-13']))
            ->assertUnprocessable()->assertJsonValidationErrors('fecha');
        $this->assertSame($antes, $this->foto());
    }

    public function test_eliminar_conserva_original_y_contraasiento_mismo_mes_medio_y_clasificacion(): void
    {
        $mov = $this->directo('EGRESO', 'RETIRO', '2026-09-10');
        $this->get($this->ruta($mov).'/eliminar')->assertOk();
        $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 0])->assertRedirect();
        $this->assertSame('-48.50', $mov->fresh()->monto);
        $this->assertNotNull($mov->fresh()->anulado_at);
        $contra = CashflowMovimiento::where('contra_origen_id', $mov->id)->sole();
        $this->assertSame('48.50', $contra->monto);
        $this->assertSame('2026-09-10', $contra->fecha->toDateString());
        $this->assertSame($mov->tipo_caja_id, $contra->tipo_caja_id);
        $this->assertSame('RETIRO', $contra->reporte_clasificacion);
        $this->assertSame('EGRESO', $contra->reporte_tipo);
        $this->assertEquals(0, CashflowMovimiento::sum('monto'));
        $this->assertSame('ELIMINAR', DB::table('cashflow_correcciones')->sole()->accion);
        $this->get($this->ruta($mov).'/editar')->assertForbidden();
        $this->putJson($this->ruta($contra), $this->datos($contra))->assertForbidden();
    }

    public function test_doble_envio_editar_y_eliminar_es_idempotente_y_no_reutiliza_clave_con_otros_datos(): void
    {
        $mov = $this->directo(); $datos = $this->datos($mov, ['motivo' => '0']);
        $this->putJson($this->ruta($mov), $datos)->assertRedirect(); $antes = $this->foto();
        $this->assertSame('0', DB::table('cashflow_correcciones')->sole()->motivo);
        $this->putJson($this->ruta($mov), $datos)->assertRedirect();
        $this->assertSame($antes, $this->foto());
        $this->putJson($this->ruta($mov), array_replace($datos, ['monto' => '99.25']))->assertUnprocessable();
        $this->assertSame($antes, $this->foto());
        $datos = ['clave' => (string) Str::uuid(), 'version' => 1, 'motivo' => 'Carga equivocada'];
        $this->deleteJson($this->ruta($mov), $datos)->assertRedirect(); $antes = $this->foto();
        $this->deleteJson($this->ruta($mov), $datos)->assertRedirect();
        $this->assertSame($antes, $this->foto());
        $this->assertSame(2, DB::table('cashflow_correcciones')->count());
        $this->assertSame(1, CashflowMovimiento::where('contra_origen_id', $mov->id)->count());
    }

    public function test_formulario_viejo_no_pisa_edicion_ni_elimina_valor_que_no_se_vio(): void
    {
        $mov = $this->directo(); $viejos = $this->datos($mov);
        $this->putJson($this->ruta($mov), $this->datos($mov))->assertRedirect(); $antes = $this->foto();
        $this->putJson($this->ruta($mov), $viejos)->assertUnprocessable();
        $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 0])->assertUnprocessable();
        $this->assertSame($antes, $this->foto());
    }

    public function test_otros_roles_no_ven_ni_escriben_forzando_rutas(): void
    {
        $mov = $this->directo(); $antes = $this->foto();
        foreach (['OPERATIVO', 'PROFESOR'] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol, 'activo' => true]));
            foreach (['/cashflow', $this->ruta($mov), $this->ruta($mov).'/editar', $this->ruta($mov).'/eliminar'] as $ruta) {
                $this->get($ruta)->assertForbidden();
            }
            $this->putJson($this->ruta($mov), $this->datos($mov))->assertForbidden();
            $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 0])->assertForbidden();
        }
        $this->assertSame($antes, $this->foto());
    }

    public function test_referencias_de_pago_caja_y_liquidacion_no_se_corrigen_como_directos(): void
    {
        foreach ([CashflowMovimiento::REF_CAJA, CashflowMovimiento::REF_PAGO_CUOTA, CashflowMovimiento::REF_LIQUIDACION] as $tipo) {
            $mov = $this->directo(); $mov->update(['referencia_tipo' => $tipo, 'referencia_id' => 1]); $antes = $this->foto();
            $this->get($this->ruta($mov).'/editar')->assertForbidden();
            $this->get($this->ruta($mov).'/eliminar')->assertForbidden();
            $this->putJson($this->ruta($mov), $this->datos($mov))->assertForbidden();
            $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 0])->assertForbidden();
            $this->assertSame($antes, $this->foto());
        }
    }

    public function test_subrubro_reservado_no_recibe_ediciones_directas(): void
    {
        $mov = $this->directo(); $antes = $this->foto();
        $reservado = Subrubro::where('es_reservado_sistema', true)->firstOrFail();
        $this->putJson($this->ruta($mov), $this->datos($mov, ['subrubro_id' => $reservado->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('subrubro_id');
        $this->assertSame($antes, $this->foto());
    }

    public function test_cashflow_inicio_reportes_historial_y_saldo_coinciden_tras_editar_y_eliminar(): void
    {
        $mov = $this->directo('EGRESO');
        $this->putJson($this->ruta($mov), $this->datos($mov))->assertRedirect();
        $this->comprobarTotales(7525);
        $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 1])->assertRedirect();
        $this->comprobarTotales(0);
    }

    public function test_historial_muestra_egreso_directo_como_salida_y_total_positivo(): void
    {
        $this->directo('EGRESO');
        $this->get('/caja/historial?desde=2026-10-01&hasta=2026-10-12')->assertOk()
            ->assertViewHas('totalEgresos', 48.50)->assertSee('−$48.50')->assertDontSee('+$48.50');
    }

    private function comprobarTotales(int $egresos): void
    {
        $this->get('/cashflow?periodo=mes&anio=2026&mes=10')->assertOk()->assertViewHas('totalEgresos', $egresos / 100);
        $this->get('/caja/historial?desde=2026-10-01&hasta=2026-10-12')->assertOk()->assertViewHas('totalEgresos', $egresos / 100);
        $reporte = app(ReporteMensualService::class)->obtener('2026-10');
        $this->assertSame($egresos, $reporte['egresos']);
        $this->get('/admin/dashboard')->assertOk()->assertViewHas('reporte', fn ($r) => $r['egresos'] === $egresos && $r['resultado'] === -$egresos);
        $this->assertEquals(48000.50 - $egresos / 100, $reporte['disponible'] / 100);
        $this->assertEquals(-$egresos / 100, CashflowMovimiento::sum('monto'));
    }

    public function test_reporte_original_se_actualiza_y_contraasiento_respeta_clasificacion_congelada(): void
    {
        $mov = $this->directo('INGRESO', 'APORTE', '2026-09-10');
        $this->putJson($this->ruta($mov), $this->datos($mov))->assertRedirect();
        $this->assertSame(7525, app(ReporteMensualService::class)->obtener('2026-09')['aportes']);
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-10')['aportes']);
        $mov->subrubro->update(['clasificacion_resultado' => 'NEGOCIO']);
        $this->deleteJson($this->ruta($mov), ['clave' => (string) Str::uuid(), 'version' => 1])->assertRedirect();
        $this->assertSame(0, app(ReporteMensualService::class)->obtener('2026-09')['aportes']);
        $this->assertSame('APORTE', CashflowMovimiento::where('contra_origen_id', $mov->id)->sole()->reporte_clasificacion);
    }

    public function test_liquidacion_pendiente_usa_editor_existente_y_pagada_permanece_intacta(): void
    {
        $liq = $this->f->liquidacion;
        $this->get('/cashflow')->assertOk()->assertSee(route('web.liquidaciones.show', [$liq->id, 'editar' => 1]), false);
        $this->get('/liquidaciones/'.$liq->id.'?editar=1')->assertOk()->assertSee('id="editar-liquidacion" open', false);
        $datos = ['monto_final' => '75.25', 'monto_anterior' => '48000.50', 'motivo' => 'Ajuste existente'];
        $this->post('/liquidaciones/'.$liq->id.'/ajustar', $datos)->assertRedirect();
        $this->assertSame('75.25', $liq->fresh()->monto_a_pagar);
        $liq->update(['estado' => Liquidacion::ESTADO_CERRADA]);
        $liq->update(['estado_pago' => Liquidacion::ESTADO_PAGO_PAGADA]);
        $antes = $this->foto();
        $this->get('/liquidaciones/'.$liq->id.'?editar=1')->assertOk()->assertDontSee('id="editar-liquidacion"', false);
        $this->post('/liquidaciones/'.$liq->id.'/ajustar', array_replace($datos, ['monto_anterior' => '75.25']))
            ->assertSessionHas('error');
        $this->assertSame($antes, $this->foto());
    }

    public function test_cambiar_medio_y_subrubro_actualiza_ambos_saldos_y_clasificacion_con_historial(): void
    {
        $mov = $this->directo('EGRESO');
        $nuevoMedio = \App\Models\TipoCaja::create(['nombre' => 'Otro banco T27', 'activo' => true, 'saldo_inicial' => '0.00']);
        $otro = $this->directo('INGRESO', 'APORTE');
        $this->putJson($this->ruta($mov), $this->datos($mov, ['tipo_caja_id' => $nuevoMedio->id, 'subrubro_id' => $otro->subrubro_id]))->assertRedirect();
        $this->assertSame('75.25', $mov->fresh()->monto);
        $this->assertSame('APORTE', $mov->fresh()->reporte_clasificacion);
        $this->assertEquals(48.50, CashflowMovimiento::where('tipo_caja_id', $this->f->medio->id)->sum('monto'));
        $this->assertEquals(75.25, CashflowMovimiento::where('tipo_caja_id', $nuevoMedio->id)->sum('monto'));
        $historia = DB::table('cashflow_correcciones')->sole();
        $this->assertSame($this->f->medio->id, json_decode($historia->antes, true)['tipo_caja_id']);
        $this->assertSame($nuevoMedio->id, json_decode($historia->despues, true)['tipo_caja_id']);
        $reporte = app(ReporteMensualService::class)->obtener('2026-10');
        $this->assertSame(12375, $reporte['aportes']);
        $this->assertSame(0, $reporte['resultado']);
        $this->assertEquals(48124.25, $reporte['disponible'] / 100);
        $this->get('/cashflow')->assertViewHas('totalIngresos', 123.75)->assertViewHas('totalEgresos', 0);
        $this->get('/caja/historial')->assertViewHas('totalIngresos', 123.75)->assertViewHas('totalEgresos', 0);
        // Cambio de tipo permitido para un rubro NEGOCIO: el editor y el reporte
        // deben coincidir con el tipo que se está eligiendo, no conservar el viejo.
        $negocio = $this->directo('EGRESO');
        $negocio->subrubro->rubro->update(['tipo' => 'INGRESO']);
        $this->putJson($this->ruta($negocio), $this->datos($negocio, ['monto' => '51.50']))->assertRedirect();
        $this->assertSame('INGRESO', $negocio->fresh()->reporte_tipo);
        $this->assertSame(5150, app(ReporteMensualService::class)->obtener('2026-10')['ingresos']);
        $this->get('/cashflow')->assertViewHas('totalIngresos', 175.25);
        $this->get('/caja/historial')->assertViewHas('totalIngresos', 175.25);
    }

    public function test_falla_al_guardar_historial_revierte_original_contraasiento_y_version(): void
    {
        $mov = $this->directo(); $antes = $this->foto();
        $evento = 'eloquent.creating: '.\App\Models\CashflowCorreccion::class;
        \Illuminate\Support\Facades\Event::listen($evento, fn () => throw new \RuntimeException('Falla simulada de historial'));
        try {
            app(\App\Services\CashflowCorreccionService::class)->eliminar($mov->id, $this->f->admin->id,
                ['clave' => (string) Str::uuid(), 'version' => 0]);
            $this->fail('La corrección se guardó sin su historial.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Falla simulada de historial', $e->getMessage());
        } finally {
            \Illuminate\Support\Facades\Event::forget($evento);
        }
        $this->assertSame($antes, $this->foto());
    }

    public function test_servicio_rechaza_actor_no_admin_o_inactivo_sin_escribir(): void
    {
        $mov = $this->directo(); $antes = $this->foto();
        foreach ([['rol' => 'OPERATIVO', 'activo' => true], ['rol' => 'PROFESOR', 'activo' => true], ['rol' => 'ADMIN', 'activo' => false]] as $actor) {
            $usuario = User::factory()->create($actor);
            try {
                app(\App\Services\CashflowCorreccionService::class)->editar($mov->id, $usuario->id, $this->datos($mov));
                $this->fail('El servicio permitió una corrección a un actor no autorizado.');
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                $this->assertStringContainsString('administrador activo', $e->getMessage());
            }
        }
        $this->assertSame($antes, $this->foto());
    }
}
