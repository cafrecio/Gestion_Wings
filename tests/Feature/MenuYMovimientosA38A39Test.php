<?php

namespace Tests\Feature;

use App\Models\{CajaOperativa, MovimientoOperativo, Subrubro, TipoCaja, User};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

/**
 * A38 y A39 de PRU-02, y la regla que Carlos fijo el 08/10/2026 al aprobar el menu:
 * el operativo tiene Movimientos en su menu, pero solo ve lo de sus rubros. «Nunca
 * sueldos, alquileres, ni nada que el admin ponga solo para el.»
 */
class MenuYMovimientosA38A39Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
    }

    private function usuario(string $rol): User
    {
        return User::factory()->create(['rol' => $rol, 'activo' => true]);
    }

    private function cargarMovimiento(User $quien, Subrubro $subrubro, float $monto): void
    {
        $caja = CajaOperativa::create([
            'usuario_operativo_id' => $quien->id,
            'apertura_at' => '2026-10-07 08:00:00',
            'cierre_at' => '2026-10-07 17:00:00',
            'estado' => CajaOperativa::ESTADO_CERRADA,
        ]);

        MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => '2026-10-07',
            'tipo_caja_id' => TipoCaja::first()->id,
            'subrubro_id' => $subrubro->id,
            'monto' => $monto,
            'usuario_id' => $quien->id,
            'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        ]);
    }

    public function test_a38_la_paginacion_habla_en_castellano(): void
    {
        $this->assertSame('es', app()->getLocale());

        $html = (new LengthAwarePaginator(range(1, 20), 76, 20, 1, ['path' => '/clases']))->links()->render();

        $this->assertStringContainsString('Mostrando', $html);
        $this->assertStringContainsString('resultados', $html);
        $this->assertStringContainsString('Siguiente', $html);
        $this->assertStringNotContainsString('Showing', $html);
        $this->assertStringNotContainsString('results', $html);
        $this->assertStringNotContainsString('Next', $html);
    }

    public function test_a39_el_menu_del_operativo_ofrece_movimientos_y_la_pantalla_abre(): void
    {
        $operativo = $this->usuario(User::ROL_OPERATIVO);

        $this->actingAs($operativo)->get(route('web.operativo.dashboard'))
            ->assertOk()
            ->assertSee('href="' . route('web.movimientos.index') . '"', false);

        $this->actingAs($operativo)->get(route('web.movimientos.index'))->assertOk();
    }

    public function test_a39_el_profesor_no_ve_movimientos_en_su_menu(): void
    {
        $this->actingAs($this->usuario(User::ROL_PROFESOR))->get(route('web.clases.index'))
            ->assertOk()
            ->assertDontSee('href="' . route('web.movimientos.index') . '"', false);
    }

    public function test_el_operativo_ve_los_movimientos_de_un_companero_pero_nada_de_los_rubros_del_admin(): void
    {
        $sandra = $this->usuario(User::ROL_OPERATIVO);
        $marcos = $this->usuario(User::ROL_OPERATIVO);
        $deOperativo = Subrubro::where('permitido_para', 'OPERATIVO')
            ->whereHas('rubro', fn ($q) => $q->where('tipo', 'INGRESO'))->with('rubro')->firstOrFail();
        $soloAdmin = Subrubro::where('permitido_para', 'ADMIN')->with('rubro')->firstOrFail();
        $rubrosSoloAdmin = \App\Models\Rubro::whereDoesntHave('subrubros', fn ($q) => $q->where('permitido_para', 'OPERATIVO'))->get();
        $this->assertNotEmpty($rubrosSoloAdmin, 'El catalogo tiene que traer al menos un rubro reservado al admin.');

        $this->cargarMovimiento($marcos, $deOperativo, 48123);
        $this->cargarMovimiento($sandra, $soloAdmin, 99777);

        $respuesta = $this->actingAs($sandra)->get(route('web.movimientos.index'))->assertOk();

        // Ve lo del compañero: el criterio es el rubro, no quien lo cargo.
        $respuesta->assertSee('48.123');
        // No ve el importe ni lo suma a los totales.
        $respuesta->assertDontSee('99.777');
        $this->assertEqualsWithDelta(48123.0, (float) $respuesta->viewData('totalIngresos') - (float) $respuesta->viewData('totalEgresos'), 0.01);
        // Tampoco ve los nombres en los filtros.
        $this->assertSame([], $respuesta->viewData('subrubros')->where('permitido_para', 'ADMIN')->pluck('nombre')->all());
        $this->assertSame([], $respuesta->viewData('rubros')->pluck('id')->intersect($rubrosSoloAdmin->pluck('id'))->all());

        // Pedirlo por la direccion tampoco lo muestra.
        $this->actingAs($sandra)->get(route('web.movimientos.index', ['subrubro_id' => $soloAdmin->id]))
            ->assertOk()->assertDontSee('99.777');

        // El admin si ve todo.
        $admin = $this->usuario(User::ROL_ADMIN);
        $this->actingAs($admin)->get(route('web.movimientos.index'))->assertOk()->assertSee('99.777')->assertSee('48.123');
    }
}
