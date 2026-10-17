<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CajaRechazoMotivoT37Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringStartsWith('wings_testing', config('database.connections.mysql.database'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo T37', 'activo' => true]);
        $this->caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativa->id,
            'estado' => 'CERRADA', 'apertura_at' => now(), 'cierre_at' => now(),
            'tipo_caja_efectivo_id' => $medio->id,
            'efectivo_inicial' => 100, 'efectivo_esperado' => 100,
            'efectivo_contado' => 100, 'cambio_retenido' => 100,
            'efectivo_retirado' => 0, 'diferencia_efectivo' => 0,
        ]);
    }

    private function rechazarInvalido(array $datos, string $mensaje): void
    {
        foreach (['web.caja.detalle', 'web.caja.resumen'] as $pantalla) {
            $origen = route($pantalla, $this->caja->id);
            $this->actingAs($this->admin)->from($origen)
                ->post(route('web.cajas.rechazar', $this->caja->id), $datos)
                ->assertRedirect($origen)->assertSessionHasErrors(['motivo' => $mensaje]);
            $this->assertSame('CERRADA', $this->caja->fresh()->estado);
            $this->assertNull($this->caja->fresh()->motivo_rechazo);
            $this->get($origen)->assertOk()
                ->assertSee('<div class="ds-flash ds-flash--error" role="alert">'.$mensaje.'</div>', false);
        }
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_rechazar_sin_motivo_muestra_el_error_en_ambas_pantallas(): void
    {
        $this->rechazarInvalido([], 'El motivo del rechazo es obligatorio.');
    }

    public function test_rechazar_con_solo_espacios_no_cambia_la_caja(): void
    {
        $this->rechazarInvalido(['motivo' => "  \t\n  "], 'El motivo del rechazo es obligatorio.');
    }

    public function test_rechazar_con_mas_de_500_caracteres_no_guarda(): void
    {
        $this->rechazarInvalido(['motivo' => str_repeat('a', 501)], 'El motivo no puede superar los 500 caracteres.');
        $this->rechazarInvalido(['motivo' => ['texto']], 'El motivo debe ser texto.');
    }

    public function test_motivo_valido_se_guarda_y_la_operativa_lo_ve_en_el_resumen(): void
    {
        $this->actingAs($this->admin)->post(route('web.cajas.rechazar', $this->caja->id), [
            'motivo' => '  Falta el comprobante de la transferencia.  ',
        ])->assertRedirect(route('web.caja.index'))->assertSessionHasNoErrors();
        $this->assertSame('RECHAZADA', $this->caja->fresh()->estado);
        $this->assertSame('Falta el comprobante de la transferencia.', $this->caja->fresh()->motivo_rechazo);
        $this->actingAs($this->operativa)->get(route('web.caja.resumen', $this->caja->id))
            ->assertOk()->assertSee('Falta el comprobante de la transferencia.');
        $this->assertSame('100.00', $this->caja->fresh()->efectivo_contado);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
    }

    public function test_servicio_directo_rechaza_vacio_y_espacios_sin_escribir(): void
    {
        foreach (['', "  \t\n "] as $motivo) {
            try {
                app(CajaService::class)->rechazarCaja($this->caja->id, $this->admin->id, $motivo);
                $this->fail('El servicio aceptó un motivo vacío.');
            } catch (ValidationException $e) {
                $this->assertSame(['El motivo del rechazo es obligatorio.'], $e->errors()['motivo']);
            }
            $this->assertSame('CERRADA', $this->caja->fresh()->estado);
            $this->assertNull($this->caja->fresh()->motivo_rechazo);
        }
    }

    public function test_servicio_directo_rechaza_mas_de_500_y_acepta_500(): void
    {
        try {
            app(CajaService::class)->rechazarCaja($this->caja->id, $this->admin->id, str_repeat('á', 501));
            $this->fail('El servicio aceptó más de 500 caracteres.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('motivo', $e->errors());
        }
        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
        app(CajaService::class)->rechazarCaja($this->caja->id, $this->admin->id, str_repeat('á', 500));
        $this->assertSame(str_repeat('á', 500), $this->caja->fresh()->motivo_rechazo);
    }

    public function test_ambos_modales_exigen_motivo_y_no_inventan_un_minimo(): void
    {
        foreach (['web.caja.detalle', 'web.caja.resumen'] as $pantalla) {
            $html = $this->actingAs($this->admin)->get(route($pantalla, $this->caja->id))
                ->assertOk()->assertDontSee('(opcional)')->getContent();
            $this->assertMatchesRegularExpression('/<input[^>]*name="motivo"[^>]*required[^>]*maxlength="500"/', $html);
        }
        $this->actingAs($this->admin)->post(route('web.cajas.rechazar', $this->caja->id), ['motivo' => 'X'])
            ->assertSessionHasNoErrors();
        $this->assertSame('X', $this->caja->fresh()->motivo_rechazo);
    }

    public function test_consultar_rechazada_historica_no_rellena_su_motivo(): void
    {
        $this->caja->update(['estado' => 'RECHAZADA', 'motivo_rechazo' => '']);
        $this->actingAs($this->operativa)->get(route('web.caja.resumen', $this->caja->id))->assertOk();
        $this->assertSame('', $this->caja->fresh()->motivo_rechazo);
    }
}
