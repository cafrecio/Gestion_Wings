<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CajaDeclarada;
use Tests\TestCase;

/**
 * T107 (Carlos, 01/11/2026): cerrar con la pantalla vieja no puede dejar un faltante que no existe.
 * Día 46.7: se veían $11.600, entraron $44.000 en otra pestaña y se cerró con $11.600: faltante falso de $44.000.
 */
class CierrePantallaViejaT107Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operativa;
    private TipoCaja $efectivo;
    private TipoCaja $banco;
    private Subrubro $ingreso;
    private CajaOperativa $caja;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $this->operativa = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $this->efectivo = TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $this->banco = TipoCaja::create(['nombre' => 'Banco T107', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos T107', 'tipo' => 'INGRESO']);
        $this->ingreso = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Ingreso T107', 'permitido_para' => 'OPERATIVO',
            'afecta_caja' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true]);
        $this->caja = CajaDeclarada::crear($this->operativa->id, $this->efectivo->id);
        $this->caja->update(['efectivo_inicial' => '11600.00']);
    }

    private function movimiento(string $monto, ?TipoCaja $cuenta = null): MovimientoOperativo
    {
        return MovimientoOperativo::create(['caja_operativa_id' => $this->caja->id, 'fecha' => today(), 'tipo_caja_id' => ($cuenta ?? $this->efectivo)->id,
            'subrubro_id' => $this->ingreso->id, 'monto' => $monto, 'usuario_id' => $this->operativa->id, 'estado' => 'ACTIVO', 'observaciones' => 'Prueba T107']);
    }

    private function cerrar(string $contado, ?string $visto, ?User $quien = null)
    {
        $datos = ['efectivo_contado' => $contado, 'cambio_retenido' => '5000'];
        if ($visto !== null) $datos['esperado_visto'] = $visto;

        return $this->actingAs($quien ?? $this->operativa)->from(route('web.caja.cierre', $this->caja->id))
            ->post(route('web.caja.cerrar', $this->caja->id), $datos);
    }

    public function test_la_pantalla_guarda_el_esperado_que_mostro(): void
    {
        $this->actingAs($this->operativa)->get(route('web.caja.cierre', $this->caja->id))
            ->assertOk()->assertSee('name="esperado_visto" value="11600.00"', false);
    }

    public function test_si_entro_un_cobro_no_cierra_y_avisa_el_esperado_nuevo(): void
    {
        $this->movimiento('44000');

        $this->cerrar('11600', '11600.00')->assertRedirect(route('web.caja.cierre', $this->caja->id))->assertSessionHasErrors('efectivo_contado');

        $this->assertStringContainsString('ahora es $55,600.00', session('errors')->first('efectivo_contado'));
        $caja = $this->caja->fresh();
        $this->assertSame('ABIERTA', $caja->estado);
        $this->assertNull($caja->efectivo_contado);
    }

    public function test_la_pantalla_vuelve_con_el_aviso_y_el_esperado_al_dia_y_ahi_cierra_sin_faltante(): void
    {
        $this->movimiento('44000');
        $this->cerrar('11600', '11600.00');

        $this->actingAs($this->operativa)->get(route('web.caja.cierre', $this->caja->id))
            ->assertOk()->assertSee('No se cerró la caja')->assertSee('$55,600.00')->assertSee('name="esperado_visto" value="55600.00"', false);

        $this->cerrar('55600', '55600.00')->assertSessionHasNoErrors()->assertSessionHas('success', 'Caja cerrada.');
        $caja = $this->caja->fresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertSame('0.00', (string) $caja->diferencia_efectivo);
    }

    public function test_si_se_cancelo_un_cobro_tambien_avisa(): void
    {
        $cobro = $this->movimiento('44000');
        $visto = app(CajaService::class)->arqueoCaja($this->caja->id)['efectivo_esperado'];
        $cobro->update(['estado' => 'CANCELADO']);

        $this->cerrar('55600', (string) $visto)->assertSessionHasErrors('efectivo_contado');

        $this->assertStringContainsString('ahora es $11,600.00', session('errors')->first('efectivo_contado'));
        $this->assertSame('ABIERTA', $this->caja->fresh()->estado);
    }

    public function test_un_movimiento_de_otra_cuenta_no_cambia_el_efectivo_y_no_molesta(): void
    {
        $this->movimiento('44000', $this->banco);

        $this->cerrar('11600', '11600.00')->assertSessionHasNoErrors();

        $this->assertSame('CERRADA', $this->caja->fresh()->estado);
    }

    public function test_un_faltante_real_con_la_pantalla_al_dia_sigue_cerrando(): void
    {
        $this->cerrar('10000', '11600.00')->assertSessionHasNoErrors();

        $caja = $this->caja->fresh();
        $this->assertSame('CERRADA', $caja->estado);
        $this->assertSame('-1600.00', (string) $caja->diferencia_efectivo);
    }

    public function test_el_duenio_cerrando_el_turno_ajeno_tambien_recibe_el_aviso(): void
    {
        $this->movimiento('44000');

        $this->cerrar('11600', '11600.00', $this->admin)->assertSessionHasErrors('efectivo_contado');

        $this->assertSame('ABIERTA', $this->caja->fresh()->estado);
    }

    public function test_el_doble_clic_sigue_rechazando_el_segundo_envio(): void
    {
        $this->cerrar('11600', '11600.00')->assertSessionHasNoErrors();

        $this->cerrar('11600', '11600.00')->assertSessionHas('error', 'La caja no está en estado editable.');
    }

    public function test_un_esperado_visto_invalido_no_cierra(): void
    {
        $this->cerrar('11600', 'abc')->assertSessionHasErrors('esperado_visto');

        $this->assertSame('ABIERTA', $this->caja->fresh()->estado);
    }

    public function test_sin_el_dato_cierra_como_antes(): void
    {
        $this->movimiento('44000');

        $this->cerrar('11600', null)->assertSessionHasNoErrors();

        $this->assertSame('-44000.00', (string) $this->caja->fresh()->diferencia_efectivo);
    }
}
