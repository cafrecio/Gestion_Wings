<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrearAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_crea_un_admin_que_puede_iniciar_sesion_y_no_lo_duplica(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);

        $this->artisan('wings:crear-admin')
            ->expectsQuestion('Email del administrador', 'admin@wings.test')
            ->expectsQuestion('Nombre del administrador', 'Administración Wings')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'patin-club-jueves')
            ->expectsOutput('Administrador creado correctamente.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@wings.test',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);

        $this->post(route('login'), [
            'email' => 'admin@wings.test',
            'password' => 'patin-club-jueves',
        ])->assertRedirect(route('admin.dashboard'));

        auth()->logout();

        $this->artisan('wings:crear-admin')
            ->expectsQuestion('Email del administrador', 'admin@wings.test')
            ->expectsOutput('Ya existe un usuario con ese email.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_rechaza_contrasena_de_menos_de_doce_caracteres(): void
    {
        $this->artisan('wings:crear-admin')
            ->expectsQuestion('Email del administrador', 'admin@wings.test')
            ->expectsQuestion('Nombre del administrador', 'Administración Wings')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'corta')
            ->expectsOutput('La contraseña debe tener al menos 12 caracteres.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_opcion_superadmin_crea_una_cuenta_protegida(): void
    {
        $this->artisan('wings:crear-admin', ['--superadmin' => true])
            ->expectsQuestion('Email del administrador', 'carlos@wings.test')
            ->expectsQuestion('Nombre del administrador', 'Carlos')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'frase-segura-carlos')
            ->expectsOutput('Administrador creado correctamente.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'carlos@wings.test',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'es_superadmin' => true,
        ]);
    }
}
