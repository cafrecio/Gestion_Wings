<?php

namespace Tests\Feature;

use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T59 (DU-24, MOV-006): el dueño que entra a «Nuevo movimiento de caja» va directo a
 * Cashflow. Antes recibía «Debés abrir la caja», cosa que él no hace.
 */
class DuenoMovimientoCajaT59Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativo;
    private TipoCaja $efectivo;
    private Subrubro $subrubro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
        // Hay un turno abierto de la operativa: es justo el que no tiene que recibir nada del dueño.
        \Tests\Support\CajaDeclarada::crear($this->operativo->id, $this->efectivo->id);
        $this->subrubro = Subrubro::where('permitido_para', 'OPERATIVO')->where('es_reservado_sistema', false)->firstOrFail();
    }

    public function test_el_duenio_que_entra_a_nuevo_movimiento_de_caja_va_a_cashflow(): void
    {
        $this->actingAs($this->admin)->get(route('web.caja.movimiento'))
            ->assertRedirect(route('web.cashflow.movimiento'));

        $this->actingAs($this->admin)->followingRedirects()->get(route('web.caja.movimiento'))
            ->assertOk()
            ->assertDontSee('Debés abrir la caja')
            ->assertSee('action="' . route('web.cashflow.movimiento.store') . '"', false);
    }

    public function test_si_el_duenio_envia_el_formulario_de_caja_no_se_registra_nada(): void
    {
        $this->actingAs($this->admin)->post(route('web.caja.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->subrubro->id,
            'monto' => '1,500.00',
            'observaciones' => 'Gasto del dueño',
        ])->assertRedirect(route('web.cashflow.movimiento'))
            ->assertSessionHas('error', fn ($e) => str_contains($e, 'Cashflow') && str_contains($e, 'No se registró nada'));

        $this->assertDatabaseCount('movimientos_operativos', 0);
        $this->assertDatabaseCount('cashflow_movimientos', 0);
        $this->assertDatabaseCount('cajas_operativas', 1);
    }

    public function test_la_operativa_sigue_cargando_movimientos_en_su_caja(): void
    {
        \App\Models\CajaOperativa::where('usuario_operativo_id', $this->operativo->id)->update(['efectivo_inicial' => 1500]);
        $this->actingAs($this->operativo)->get(route('web.caja.movimiento'))->assertOk();

        $this->actingAs($this->operativo)->post(route('web.caja.movimiento.store'), [
            'tipo_caja_id' => $this->efectivo->id,
            'subrubro_id' => $this->subrubro->id,
            'monto' => '1,500.00',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('movimientos_operativos', ['subrubro_id' => $this->subrubro->id, 'monto' => 1500]);
    }
}
