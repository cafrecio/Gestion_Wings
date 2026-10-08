<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A56: en el teléfono, Cashflow se salía de la pantalla.
 *
 * Su barra de filtros no usaba la barra compartida que quedó responsive al cerrar A19:
 * armaba su propia grilla a mano, con cuatro columnas fijas —`100px 1fr 1fr 1fr`—, que no
 * se apila nunca. Eso empujaba el ancho de toda la pantalla: el cuarto filtro quedaba
 * afuera y los totales se leían a medias.
 *
 * Lo que se puede comprobar sin navegador es que no vuelva el ancho fijo y que los bloques
 * estén declarados para apilarse. Lo que se ve, va por captura.
 */
class CashflowEntraEnElCelularTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_filtros_no_tienen_una_grilla_de_ancho_fijo(): void
    {
        $vista = file_get_contents(base_path('resources/views/cashflow/index.blade.php'));

        $this->assertStringNotContainsString(
            'grid-template-columns: 100px 1fr 1fr 1fr',
            $vista,
            'La grilla fija de cuatro columnas es la que no entra en el teléfono.'
        );
    }

    public function test_los_filtros_y_los_totales_se_apilan_en_pantalla_angosta(): void
    {
        $vista = file_get_contents(base_path('resources/views/cashflow/index.blade.php'));

        $this->assertStringContainsString('repeat(2,minmax(0,1fr))', $vista, 'Los filtros móviles usan columnas flexibles.');
        $this->assertStringContainsString('@media(min-width:1024px)', $vista, 'La grilla de escritorio se limita a pantallas amplias.');
        $this->assertStringContainsString('flex-wrap', $vista, 'Los totales tienen que poder bajar de renglón.');
    }

    public function test_la_pantalla_sigue_funcionando_para_el_admin(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        $this->actingAs($admin)
            ->get(route('web.cashflow.index'))
            ->assertOk()
            ->assertSee('Cashflow');
    }
}
