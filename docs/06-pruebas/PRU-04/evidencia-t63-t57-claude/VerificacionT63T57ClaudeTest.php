<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, Clase, Deporte, Grupo, Nivel, TipoCaja, User};
use App\Services\CajaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Controles propios de Claude al verificar T63 y T57 (32e09e5, c2db5d4), 18/10/2026.
 * Se corre desde esta carpeta, no desde tests/, para no cambiar el conteo de la carpeta compartida.
 */
class VerificacionT63T57ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-18 10:00:00'));
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
    }

    public function test_cambiar_el_medio_con_una_caja_cerrada_sin_validar_no_toca_su_arqueo(): void
    {
        $viejo = TipoCaja::create(['nombre' => 'Billetes', 'activo' => true]);
        $nuevo = TipoCaja::create(['nombre' => 'Cajón nuevo', 'activo' => true]);
        $servicio = app(CajaService::class);
        $servicio->configurarMostrador($viejo->id, $this->admin->id);
        $operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $caja = CajaOperativa::create(['usuario_operativo_id' => $operativa->id, 'estado' => 'CERRADA',
            'apertura_at' => now()->subHours(3), 'cierre_at' => now()->subHour(), 'tipo_caja_efectivo_id' => $viejo->id,
            'efectivo_inicial' => 1000, 'efectivo_esperado' => 1000, 'efectivo_contado' => 1000,
            'cambio_retenido' => 1000, 'efectivo_retirado' => 0, 'diferencia_efectivo' => 0]);
        $arqueoAntes = $servicio->arqueoCaja($caja->id);

        $servicio->configurarMostrador($nuevo->id, $this->admin->id);

        $this->assertSame($nuevo->id, $servicio->tipoCajaMostradorId());
        $this->assertSame($viejo->id, (int) $caja->fresh()->tipo_caja_efectivo_id);
        $this->assertEquals($arqueoAntes, $servicio->arqueoCaja($caja->id));
    }

    public function test_una_clase_de_hoy_cuenta_como_futura_y_una_de_ayer_no(): void
    {
        $deporte = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Inicial'])->id, 'activo' => true]);
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => '2026-10-18', 'hora_inicio' => '08:00', 'hora_fin' => '09:00']);

        $this->actingAs($this->admin)->patchJson(route('web.grupos.toggle-activo', $grupo->id))
            ->assertStatus(422)->assertJson(['error' => 'No se puede desactivar el grupo porque tiene 1 clase futura.']);

        $clase->update(['fecha' => '2026-10-17']);
        $this->actingAs($this->admin)->patchJson(route('web.grupos.toggle-activo', $grupo->id))
            ->assertOk()->assertJson(['activo' => false]);
    }
}
