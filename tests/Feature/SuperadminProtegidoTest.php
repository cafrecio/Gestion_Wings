<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminProtegidoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_comun_no_ve_al_superadmin_en_el_listado(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('web.usuarios.index'))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) =>
                $usuarios->contains('id', $admin->id)
                && !$usuarios->contains('id', $superadmin->id)
            );
    }

    public function test_superadmin_se_ve_a_si_mismo_y_al_resto_en_el_listado(): void
    {
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);
        $admin = $this->crearAdmin();

        $this->actingAs($superadmin)
            ->get(route('web.usuarios.index'))
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) =>
                $usuarios->contains('id', $superadmin->id)
                && $usuarios->contains('id', $admin->id)
            );
    }

    public function test_admin_comun_no_puede_abrir_la_edicion_del_superadmin(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->get(route('web.usuarios.edit', $superadmin))
            ->assertForbidden();
    }

    public function test_admin_comun_no_puede_editar_los_datos_del_superadmin(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $superadmin), $this->datosUsuario($superadmin, [
                'name' => 'Nombre alterado',
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'id' => $superadmin->id,
            'name' => 'Nombre alterado',
        ]);
    }

    public function test_admin_comun_no_puede_cambiar_la_contrasena_del_superadmin(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $superadmin), $this->datosUsuario($superadmin, [
                'password' => 'contrasena-nueva-segura',
                'password_confirmation' => 'contrasena-nueva-segura',
            ]))
            ->assertForbidden();

        $this->assertTrue(Hash::check('password', $superadmin->fresh()->password));
    }

    public function test_admin_comun_no_puede_cambiar_el_rol_del_superadmin(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $superadmin), $this->datosUsuario($superadmin, [
                'rol' => User::ROL_OPERATIVO,
            ]))
            ->assertForbidden();

        $this->assertSame(User::ROL_ADMIN, $superadmin->fresh()->rol);
    }

    public function test_admin_comun_no_puede_desactivar_al_superadmin(): void
    {
        $admin = $this->crearAdmin();
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);

        $this->actingAs($admin)
            ->patch(route('web.usuarios.toggle-activo', $superadmin))
            ->assertForbidden();

        $this->assertTrue((bool) $superadmin->fresh()->activo);
    }

    public function test_superadmin_puede_administrar_a_otro_admin(): void
    {
        $superadmin = $this->crearAdmin(['es_superadmin' => true]);
        $admin = $this->crearAdmin();

        $this->actingAs($superadmin)
            ->put(route('web.usuarios.update', $admin), $this->datosUsuario($admin, [
                'name' => 'Administradora del club',
            ]))
            ->assertRedirect(route('web.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Administradora del club',
        ]);

        $this->patch(route('web.usuarios.toggle-activo', $admin))
            ->assertRedirect();
        $this->assertFalse((bool) $admin->fresh()->activo);
    }

    public function test_pantalla_web_no_puede_crear_una_cuenta_protegida(): void
    {
        $admin = $this->crearAdmin();

        $this->actingAs($admin)
            ->post(route('web.usuarios.store'), [
                'name' => 'Otra administración',
                'email' => 'otra-admin@example.com',
                'password' => 'contrasena-segura',
                'password_confirmation' => 'contrasena-segura',
                'rol' => User::ROL_ADMIN,
                'es_superadmin' => true,
            ])
            ->assertRedirect(route('web.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'otra-admin@example.com',
            'es_superadmin' => false,
        ]);
    }

    public function test_pantalla_web_no_puede_marcar_una_cuenta_existente_como_protegida(): void
    {
        $admin = $this->crearAdmin();
        $otroAdmin = $this->crearAdmin();

        $this->actingAs($admin)
            ->put(route('web.usuarios.update', $otroAdmin), $this->datosUsuario($otroAdmin, [
                'es_superadmin' => true,
            ]))
            ->assertRedirect(route('web.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'id' => $otroAdmin->id,
            'es_superadmin' => false,
        ]);
    }

    private function crearAdmin(array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'es_superadmin' => false,
        ], $atributos));
    }

    private function datosUsuario(User $usuario, array $cambios = []): array
    {
        return array_merge([
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
        ], $cambios);
    }
}
