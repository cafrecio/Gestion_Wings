<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoSinPermisoTest extends TestCase
{
    use RefreshDatabase;

    public function test_profesor_sin_permiso_en_alumnos(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.alumnos.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_caja(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.caja.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_grupos(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.grupos.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_cashflow(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.cashflow.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_liquidaciones(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.liquidaciones.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_usuarios(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.usuarios.index', 'web.clases.index');
    }

    public function test_profesor_sin_permiso_en_configuraciones(): void
    {
        $this->comprobarRol(User::ROL_PROFESOR, 'web.configuraciones.index', 'web.clases.index');
    }

    public function test_operativo_sin_permiso_en_cashflow(): void
    {
        $this->comprobarRol(User::ROL_OPERATIVO, 'web.cashflow.index', 'web.operativo.dashboard');
    }

    public function test_operativo_sin_permiso_en_liquidaciones(): void
    {
        $this->comprobarRol(User::ROL_OPERATIVO, 'web.liquidaciones.index', 'web.operativo.dashboard');
    }

    public function test_operativo_sin_permiso_en_usuarios(): void
    {
        $this->comprobarRol(User::ROL_OPERATIVO, 'web.usuarios.index', 'web.operativo.dashboard');
    }

    public function test_operativo_sin_permiso_en_configuraciones(): void
    {
        $this->comprobarRol(User::ROL_OPERATIVO, 'web.configuraciones.index', 'web.operativo.dashboard');
    }

    private function comprobarRol(string $rol, string $ruta, string $inicio): void
    {
        $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);
        $this->actingAs($usuario);
        $this->comprobarRechazo(route($ruta), $inicio);
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_admin_rechazado_en_cuenta_protegida_vuelve_a_su_tablero(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $protegido = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true, 'es_superadmin' => true]);
        $this->actingAs($admin);
        $this->comprobarRechazo(route('web.usuarios.edit', $protegido), 'admin.dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_sin_sesion_se_sigue_pidiendo_ingresar(): void
    {
        $this->get(route('web.cashflow.index'))->assertRedirect(route('login'));
    }

    public function test_cuenta_inactiva_no_conserva_acceso(): void
    {
        $inactivo = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => false]);
        $this->actingAs($inactivo)->get(route('web.cashflow.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function comprobarRechazo(string $url, string $inicio): void
    {
        $respuesta = $this->get($url)
            ->assertForbidden()
            ->assertSeeText('No podés entrar a esta sección')
            ->assertSeeText('No tenés permiso para abrir esta sección con tu usuario.');

        $documento = new \DOMDocument();
        @$documento->loadHTML('<?xml encoding="utf-8" ?>'.$respuesta->getContent());
        $botones = (new \DOMXPath($documento))->query('//a[normalize-space(.)="Volver"]');
        $this->assertCount(1, $botones);
        $this->assertSame(route($inicio), $botones->item(0)->getAttribute('href'));
        $this->get(route($inicio))->assertOk();
    }
}
