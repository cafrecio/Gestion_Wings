<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PruebaSinConfirmaciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba por días (Carlos, 28/10/2026): en el sitio de prueba las confirmaciones nativas se
 * aceptan solas para que el Chrome automático no se trabe. Nunca en producción.
 */
class PruebaSinConfirmacionesTest extends TestCase
{
    use RefreshDatabase;

    private function paginaDelDueno(): string
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);

        return $this->actingAs($admin)->get(route('web.usuarios.index'))->assertOk()->getContent();
    }

    public function test_sin_la_variable_la_pagina_no_se_marca(): void
    {
        config(['app.prueba_sin_confirmaciones' => false]);

        $this->assertStringNotContainsString('data-sin-confirmaciones', $this->paginaDelDueno());
    }

    public function test_con_la_variable_en_el_sitio_de_prueba_la_pagina_se_marca(): void
    {
        config(['app.prueba_sin_confirmaciones' => true]);

        $this->assertStringContainsString(' data-sin-confirmaciones', $this->paginaDelDueno());
    }

    public function test_en_produccion_nunca_se_activa_aunque_la_variable_este_escrita(): void
    {
        config(['app.prueba_sin_confirmaciones' => true]);
        $this->app['env'] = 'production';

        $this->assertFalse(PruebaSinConfirmaciones::activa());
    }

    public function test_el_modulo_que_acepta_solo_lo_hace_con_la_marca_y_carga_primero(): void
    {
        $js = file_get_contents(resource_path('js/prueba-sin-confirmaciones.js'));
        $this->assertStringContainsString("hasAttribute('data-sin-confirmaciones')", $js);
        $this->assertStringStartsWith("import './prueba-sin-confirmaciones';", file_get_contents(resource_path('js/app.js')));
    }
}
