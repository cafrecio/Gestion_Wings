<?php

namespace Tests\Evidencia;

use App\Models\{Deporte, Grupo, GrupoPlan, Nivel, User};
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproductor de evidencia, fuera de la suite permanente. Pide las paginas reales a
 * Laravel y las guarda para capturarlas con Chrome servidas junto a los assets compilados.
 *
 *   DB_DATABASE=wings_testing_claude php vendor/bin/phpunit docs/06-pruebas/PRU-02/evidencia/a26-a38-a39-a44/PaginasA26A39Test.php
 *
 * Deja los HTML en public/_capturas-a26-a39/, que se borra al terminar de capturar.
 */
class PaginasA26A39Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_las_paginas(): void
    {
        Carbon::setTestNow('2026-10-07 10:00:00');
        $this->seed(CatalogosSeeder::class);
        $grupo = Grupo::create(['deporte_id' => Deporte::first()->id, 'nivel_id' => Nivel::first()->id, 'activo' => true]);
        GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);
        $operativo = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $destino = public_path('_capturas-a26-a39');
        @mkdir($destino, 0777, true);
        $guardar = fn (string $nombre, string $html) => file_put_contents("$destino/$nombre.html", $html);

        // A39: el menu del operativo.
        $guardar('a39-menu-operativo', $this->actingAs($operativo)->get(route('web.operativo.dashboard'))->assertOk()->getContent());
        $guardar('a39-movimientos-operativo', $this->actingAs($operativo)->get(route('web.movimientos.index'))->assertOk()->getContent());

        // A26: alta con fecha de nacimiento de un menor y de un mayor. Se manda el
        // formulario incompleto para que vuelva con los datos cargados.
        foreach (['menor' => '2016-03-10', 'mayor' => '2000-01-01'] as $caso => $nacimiento) {
            $this->actingAs($operativo)->from(route('web.alumnos.create'))
                ->post(route('web.alumnos.store'), ['fecha_nacimiento' => $nacimiento, 'fecha_alta' => '2026-10-02'])
                ->assertSessionHasErrors();
            $guardar("a26-alta-$caso", $this->actingAs($operativo)->get(route('web.alumnos.create'))->assertOk()->getContent());
        }

        $this->assertFileExists("$destino/a26-alta-menor.html");
        Carbon::setTestNow();
    }
}
