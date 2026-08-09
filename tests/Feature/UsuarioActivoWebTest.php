<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioActivoWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create([
            'email' => 'inactivo@example.com',
            'password' => 'password',
            'activo' => false,
        ]);

        $this->from(route('login'))
            ->post('/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_sesion_emitida_se_cierra_si_el_usuario_fue_desactivado(): void
    {
        $usuario = User::factory()->create([
            'rol' => User::ROL_PROFESOR,
            'activo' => false,
        ]);

        $this->actingAs($usuario)
            ->get(route('web.clases.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
