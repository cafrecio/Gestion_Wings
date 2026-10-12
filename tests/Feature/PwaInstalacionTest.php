<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T11: Pruebas de instalabilidad de Wings como aplicación (PWA).
 *
 * Verifica que el manifest cumpla con los requisitos de instalación de Chrome,
 * que declare el punto de arranque en /login (decisión de Carlos del 12/10/2026),
 * que las plantillas lo enlacen correctamente y que un usuario con sesión iniciada
 * sea llevado de inmediato al inicio de su rol sin pedir credenciales nuevamente.
 */
class PwaInstalacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_es_valido_y_declara_arranque_en_login(): void
    {
        $manifestPath = public_path('site.webmanifest');
        $this->assertFileExists($manifestPath, 'Debe existir public/site.webmanifest');

        $contenido = file_get_contents($manifestPath);
        $manifest = json_decode($contenido, true);

        $this->assertIsArray($manifest, 'El archivo site.webmanifest debe ser un JSON válido.');
        $this->assertSame('Wings — Gestión', $manifest['name'] ?? null);
        $this->assertSame('Wings', $manifest['short_name'] ?? null);
        $this->assertSame('/login', $manifest['start_url'] ?? null, 'La pantalla de arranque de la app debe ser /login');
        $this->assertSame('/', $manifest['scope'] ?? null, 'El scope debe abarcar toda la aplicación (/)');
        $this->assertSame('/login', $manifest['id'] ?? null, 'El ID de la app debe estar definido');
        $this->assertSame('standalone', $manifest['display'] ?? null, 'display debe ser standalone para abrir sin barra de navegación');
        $this->assertSame('#0F172A', $manifest['theme_color'] ?? null);
        $this->assertSame('#080C14', $manifest['background_color'] ?? null);

        $this->assertNotEmpty($manifest['icons'] ?? [], 'Debe declarar íconos de aplicación');

        $tamanios = [];
        $purposes = [];
        foreach ($manifest['icons'] as $icon) {
            $rutaFisica = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($rutaFisica, "El archivo de ícono {$icon['src']} debe existir en public/");
            $tamanios[] = $icon['sizes'] ?? '';
            $purposes[] = $icon['purpose'] ?? 'any';
        }

        $this->assertContains('192x192', $tamanios, 'Debe incluir ícono de 192x192');
        $this->assertContains('512x512', $tamanios, 'Debe incluir ícono de 512x512');
        $this->assertContains('maskable', $purposes, 'Debe incluir ícono maskable para Android');
        $this->assertContains('any', $purposes, 'Debe incluir ícono any');
    }

    public function test_login_y_plantillas_enlazan_el_manifest(): void
    {
        // 1. Plantilla compartida canónica ds-app
        $layoutContent = file_get_contents(resource_path('views/layouts/ds-app.blade.php'));
        $this->assertStringContainsString('site.webmanifest', $layoutContent);

        // 2. Respuesta HTTP del Login (la pantalla de arranque de la app instalada)
        $res = $this->get('/login');
        $res->assertOk();
        $res->assertSee('site.webmanifest');
        $res->assertSee('name="theme-color" content="#0F172A"', false);
    }

    public function test_usuario_autenticado_al_abrir_la_app_en_login_es_redirigido_al_inicio_de_su_rol(): void
    {
        // ADMIN -> Inicio de admin
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $resAdmin = $this->actingAs($admin)->get('/login');
        $resAdmin->assertRedirect(route('admin.dashboard'));

        // OPERATIVO -> Inicio de operativo
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $resOperativo = $this->actingAs($operativo)->get('/login');
        $resOperativo->assertRedirect(route('web.operativo.dashboard'));

        // PROFESOR -> Clases
        $profesor = User::factory()->create(['rol' => User::ROL_PROFESOR, 'activo' => true]);
        $resProfesor = $this->actingAs($profesor)->get('/login');
        $resProfesor->assertRedirect(route('web.clases.index'));
    }
}
