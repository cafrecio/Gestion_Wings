<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T80: una dirección con texto donde va un número es «Página no disponible», no un error.
 * Lo encontró la prueba técnica de PRU-04 (25/10): /liquidaciones/crear mal escrito daba 500.
 */
class DireccionConTextoT80Test extends TestCase
{
    use RefreshDatabase;

    public function test_texto_en_lugar_de_numero_da_pagina_no_disponible(): void
    {
        config(['app.debug' => false]);
        $duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);

        foreach (['/liquidaciones/create', '/alumnos/abc', '/clases/abc', '/caja/abc/detalle', '/grupos/abc', '/profesores/abc', '/recibos/cuota/abc'] as $url) {
            $this->actingAs($duenio)->get($url)->assertNotFound()->assertSee('Página no disponible');
        }
    }

    public function test_los_numeros_y_las_rutas_con_texto_propio_siguen_andando(): void
    {
        $duenio = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);

        $this->actingAs($duenio)->get('/liquidaciones/crear')->assertOk();
        $this->actingAs($duenio)->get('/alumnos/create')->assertOk();
        $this->actingAs($duenio)->get('/alumnos/999999')->assertNotFound();
    }
}
