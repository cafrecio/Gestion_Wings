<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreflightCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:clave-de-prueba-no-real',
            'app.url' => 'https://wings.gestionar-te.com.ar',
            // Sobre la conexion por defecto, no sobre una fija: la suite paso de
            // SQLite a MariaDB y esta linea apuntaba a un motor que ya no se usa,
            // asi que el preflight leia el usuario real -root- y rechazaba.
            'database.connections.'.config('database.default').'.username' => 'wings_app',
            'session.secure' => true,
            'session.encrypt' => true,
            'session.same_site' => 'strict',
            'session.domain' => null,
            'logging.channels.single.level' => 'warning',
        ]);

        User::factory()->create([
            'email' => 'administracion@wings.test.ar',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
        ]);
    }

    public function test_aprueba_una_configuracion_de_produccion_completa(): void
    {
        $this->artisan('wings:preflight')
            ->expectsOutput('[OK] APP_ENV=production')
            ->expectsOutput('[OK] SESSION_DOMAIN vacío')
            ->expectsOutput('[OK] Administrador activo presente')
            ->expectsOutput('Preflight aprobado.')
            ->assertSuccessful();
    }

    public function test_falla_con_codigo_distinto_de_cero_si_un_valor_es_inseguro(): void
    {
        config()->set('app.debug', true);

        $this->artisan('wings:preflight')
            ->expectsOutput('[ERROR] APP_DEBUG=false: Un error podría mostrar código y claves.')
            ->assertFailed();
    }

    public function test_superadmin_protegido_cuenta_como_administrador_activo(): void
    {
        User::query()->delete();
        User::factory()->create([
            'email' => 'carlos@wings.test.ar',
            'rol' => User::ROL_ADMIN,
            'activo' => true,
            'es_superadmin' => true,
        ]);

        $this->artisan('wings:preflight')
            ->expectsOutput('[OK] Administrador activo presente')
            ->expectsOutput('Preflight aprobado.')
            ->assertSuccessful();
    }
}
