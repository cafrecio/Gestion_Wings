<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MedioPagoCajonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $tipoCajaMostrador;
    private TipoCaja $tipoCajaOtro;
    private CajaService $cajaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));

        $this->admin = User::factory()->create([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->operativo = User::factory()->create([
            'rol' => User::ROL_OPERATIVO,
            'activo' => true,
        ]);

        $this->tipoCajaMostrador = TipoCaja::create([
            'nombre' => 'Efectivo Mostrador',
            'abreviatura' => 'EFT',
            'saldo_inicial' => 1000,
            'activo' => true,
        ]);

        $this->tipoCajaOtro = TipoCaja::create([
            'nombre' => 'Efectivo Alternativo',
            'abreviatura' => 'EFA',
            'saldo_inicial' => 500,
            'activo' => true,
        ]);

        $this->cajaService = app(CajaService::class);
        $this->cajaService->configurarMostrador($this->tipoCajaMostrador->id, $this->admin->id);
    }

    public function test_no_se_puede_desactivar_el_medio_de_pago_del_mostrador_via_toggle_web(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('web.tipos-caja.toggle-activo', $this->tipoCajaMostrador->id));

        $response->assertSessionHas('error', 'No se puede desactivar porque es el medio de pago del cajón de mostrador.');
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_el_medio_de_pago_del_mostrador_via_toggle_json(): void
    {
        $response = $this->actingAs($this->admin)
            ->patchJson(route('web.tipos-caja.toggle-activo', $this->tipoCajaMostrador->id));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede desactivar porque es el medio de pago del cajón de mostrador.',
            ]);
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_no_se_puede_desactivar_el_medio_del_mostrador_via_update(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('web.tipos-caja.update', $this->tipoCajaMostrador->id), [
                'nombre' => 'Efectivo Mostrador',
                'abreviatura' => 'EFT',
                'saldo_inicial' => 1000,
                'activo' => 0,
            ]);

        $response->assertSessionHasErrors(['activo']);
        $this->assertTrue($this->tipoCajaMostrador->fresh()->activo);
    }

    public function test_se_puede_desactivar_otro_medio_de_pago(): void
    {
        $response = $this->actingAs($this->admin)
            ->patch(route('web.tipos-caja.toggle-activo', $this->tipoCajaOtro->id));

        $response->assertSessionHas('success', 'Tipo de caja desactivado.');
        $this->assertFalse($this->tipoCajaOtro->fresh()->activo);
    }

    public function test_duenio_puede_cambiar_medio_del_cajon_cuando_no_hay_caja_abierta(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('web.caja.configuracion.store'), [
                'tipo_caja_id' => $this->tipoCajaOtro->id,
            ]);

        $response->assertRedirect(route('web.caja.index'))
            ->assertSessionHas('success', 'Medio de efectivo configurado.');

        $this->assertEquals($this->tipoCajaOtro->id, $this->cajaService->tipoCajaMostradorId());

        // Verificar trazabilidad de quién y cuándo
        $mostrador = \Illuminate\Support\Facades\DB::table('caja_mostrador')->where('id', 1)->first();
        $this->assertEquals($this->admin->id, $mostrador->configurado_por_id);
        $this->assertEquals('2026-10-18 10:00:00', $mostrador->configurado_at);
    }

    public function test_duenio_no_puede_cambiar_medio_del_cajon_si_hay_caja_abierta(): void
    {
        // Abrir una caja
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_efectivo_id' => $this->tipoCajaMostrador->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);

        // Verificar que el servicio lance DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.');

        $this->cajaService->configurarMostrador($this->tipoCajaOtro->id, $this->admin->id);
    }

    public function test_web_rechaza_cambio_de_medio_del_cajon_si_hay_caja_abierta(): void
    {
        // Abrir una caja
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_efectivo_id' => $this->tipoCajaMostrador->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('web.caja.configuracion.store'), [
                'tipo_caja_id' => $this->tipoCajaOtro->id,
            ]);

        $response->assertSessionHas('error', 'No se puede cambiar el medio del cajón mientras haya una caja abierta. Cerrá la caja abierta antes de cambiarlo.');
        $this->assertEquals($this->tipoCajaMostrador->id, $this->cajaService->tipoCajaMostradorId());
    }

    public function test_vista_configuracion_con_caja_abierta_deshabilita_selector_y_muestra_aviso(): void
    {
        CajaOperativa::create([
            'usuario_operativo_id' => $this->operativo->id,
            'tipo_caja_efectivo_id' => $this->tipoCajaMostrador->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('web.caja.configuracion'));

        $response->assertOk();
        $response->assertSee('No se puede cambiar el medio del cajón mientras haya una caja abierta.');
        $response->assertSee('disabled', false);
        $response->assertDontSee('ds-btn--primary', false);
    }

    public function test_vista_configuracion_sin_caja_abierta_permite_guardar(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('web.caja.configuracion'));

        $response->assertOk();
        $response->assertDontSee('No se puede cambiar el medio del cajón mientras haya una caja abierta.');
        $response->assertSee('ds-btn--primary', false);
        $response->assertSee('Guardar');
    }
}
