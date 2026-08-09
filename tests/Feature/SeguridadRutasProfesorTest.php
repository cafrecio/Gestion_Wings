<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadRutasProfesorTest extends TestCase
{
    use RefreshDatabase;

    public function test_profesor_no_puede_entrar_a_dominios_operativos_o_de_dinero(): void
    {
        $profesor = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        foreach ([
            'web.caja.index',
            'web.alumnos.index',
            'web.movimientos.index',
            'web.operativo.dashboard',
            'web.grupos.index',
        ] as $routeName) {
            $this->actingAs($profesor)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }

    public function test_profesor_conserva_acceso_a_clases(): void
    {
        $profesor = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'activo' => true,
        ]);

        $this->actingAs($profesor)
            ->get(route('web.clases.index'))
            ->assertOk();
    }
}
