<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Controles propios de Claude al verificar T37 (f0e2509), 17/10/2026. */
class VerificacionT37ClaudeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $medio = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->caja = CajaOperativa::create([
            'usuario_operativo_id' => $this->operativa->id,
            'estado' => 'CERRADA', 'apertura_at' => now(), 'cierre_at' => now(),
            'tipo_caja_efectivo_id' => $medio->id,
            'efectivo_inicial' => 100, 'efectivo_esperado' => 100,
            'efectivo_contado' => 100, 'cambio_retenido' => 100,
            'efectivo_retirado' => 0, 'diferencia_efectivo' => 0,
        ]);
    }

    public function test_la_operativa_no_puede_rechazar_aunque_ponga_motivo(): void
    {
        $this->actingAs($this->operativa)
            ->post(route('web.cajas.rechazar', $this->caja->id), ['motivo' => 'Me la rechazo'])
            ->assertForbidden();

        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
        $this->assertNull($this->caja->fresh()->motivo_rechazo);
    }

    public function test_500_caracteres_con_acentos_se_aceptan(): void
    {
        $motivo = str_repeat('ñ', 500);

        $this->actingAs($this->admin)
            ->post(route('web.cajas.rechazar', $this->caja->id), ['motivo' => $motivo])
            ->assertSessionHasNoErrors();

        $this->assertSame('RECHAZADA', $this->caja->fresh()->estado);
        $this->assertSame($motivo, $this->caja->fresh()->motivo_rechazo);
    }

    public function test_el_motivo_se_guarda_sin_espacios_en_los_bordes(): void
    {
        $this->actingAs($this->admin)
            ->post(route('web.cajas.rechazar', $this->caja->id), ['motivo' => '   Falta un ingreso de $5.000   '])
            ->assertSessionHasNoErrors();

        $this->assertSame('Falta un ingreso de $5.000', $this->caja->fresh()->motivo_rechazo);
    }
}
