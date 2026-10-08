<?php

namespace Tests\Evidencia;

use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Reproductor de evidencia, fuera de la suite: el menú lateral del admin y del operativo. */
class PaginasMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_las_paginas(): void
    {
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['name' => 'Carlos Bonifacio', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['name' => 'Sandra Vidal', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $destino = public_path('_capturas-a26-a39');
        @mkdir($destino, 0777, true);

        file_put_contents("$destino/menu-admin.html", $this->actingAs($admin)->get(route('web.cobranza.index'))->assertOk()->getContent());
        file_put_contents("$destino/menu-operativo.html", $this->actingAs($operativo)->get(route('web.cobranza.index'))->assertOk()->getContent());
        $this->assertFileExists("$destino/menu-operativo.html");
    }
}
