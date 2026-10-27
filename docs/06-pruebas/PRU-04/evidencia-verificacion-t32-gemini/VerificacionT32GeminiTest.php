<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LimiteConRelojDetenido;
use App\Support\RelojSimulado;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas independientes de verificación para T32 (Gemini CAB - 27/10/2026).
 *
 * Verifica:
 * 1. Bloqueo al 6º intento fallido dentro del minuto (429) y que la clave correcta también recibe 429.
 * 2. Liberación del bloqueo transcurrido el minuto (60 segundos).
 * 3. Firma por IP: rotar el email en cada intento no evade el límite.
 * 4. Contadores separados: 60 reportes CSP no consumen intentos de login.
 * 5. Contadores separados inverso: login bloqueado no bloquea el reporte CSP (204).
 * 6. Entorno de producción: RelojSimulado se ignora por completo y nunca se registra LimiteConRelojDetenido.
 * 7. LimiteConRelojDetenido: bypass únicamente si RelojSimulado::detenido() es true.
 */
class VerificacionT32GeminiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        RelojSimulado::aplicar(null, 'testing');
        parent::tearDown();
    }

    /**
     * 1. Con clave equivocada, el 6º intento dentro del minuto se rechaza (429).
     * Una vez bloqueada la IP, el intento con clave correcta también se rechaza (429).
     */
    public function test_login_bloquea_al_sexto_intento_fallido_y_con_clave_correcta_tambien_recibe_429(): void
    {
        User::factory()->create([
            'email' => 'verif-t32@wings.test',
            'password' => 'clave-correcta-123',
            'activo' => true,
        ]);

        $ip = '192.168.50.1';

        // 5 intentos fallidos
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
                'email' => 'verif-t32@wings.test',
                'password' => 'clave-incorrecta',
            ]);
            $response->assertRedirect();
            $response->assertSessionHasErrors('email');
        }

        // 6º intento, con clave CORRECTA: la IP está throttled, debe recibir 429
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'verif-t32@wings.test',
            'password' => 'clave-correcta-123',
        ]);
        $response->assertTooManyRequests();
    }

    /**
     * 2. Al transcurrir el minuto (60 segundos), el límite se libera y permite el ingreso.
     */
    public function test_bloqueo_de_login_se_libera_al_pasar_el_minuto(): void
    {
        User::factory()->create([
            'email' => 'libera-t32@wings.test',
            'password' => 'clave-correcta-123',
            'activo' => true,
        ]);

        $ip = '192.168.50.2';
        $inicio = Carbon::now();
        Carbon::setTestNow($inicio);

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
                'email' => 'libera-t32@wings.test',
                'password' => 'clave-incorrecta',
            ])->assertRedirect();
        }

        // Intento 6: bloqueado
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'libera-t32@wings.test',
            'password' => 'clave-correcta-123',
        ])->assertTooManyRequests();

        // Avanzar el tiempo 61 segundos
        Carbon::setTestNow($inicio->copy()->addSeconds(61));

        // Intento 7: ahora debe permitir el ingreso con clave correcta
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'libera-t32@wings.test',
            'password' => 'clave-correcta-123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    /**
     * 3. El límite es por dirección IP: cambiar el correo en cada intento no evade el límite.
     */
    public function test_cambiar_correo_en_cada_intento_no_evade_el_limite_de_la_ip(): void
    {
        $ip = '192.168.50.3';

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
                'email' => "intento-usuario-{$i}@wings.test",
                'password' => 'clave-aleatoria',
            ])->assertRedirect();
        }

        // 6º intento con un 6º usuario diferente: sigue bloqueado
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'intento-usuario-6@wings.test',
            'password' => 'clave-aleatoria',
        ])->assertTooManyRequests();
    }

    /**
     * 4. Contadores separados: 60 reportes de CSP no consumen intentos de login.
     */
    public function test_reportes_csp_no_consumen_intentos_de_login(): void
    {
        User::factory()->create([
            'email' => 'separado-csp@wings.test',
            'password' => 'clave-correcta-123',
            'activo' => true,
        ]);

        $ip = '192.168.50.4';

        // 60 avisos de seguridad CSP
        for ($aviso = 1; $aviso <= 60; $aviso++) {
            $this->call(
                'POST',
                '/csp-reporte',
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/csp-report', 'REMOTE_ADDR' => $ip],
                json_encode(['csp-report' => ['document-uri' => 'https://wings.test/login', 'violated-directive' => 'script-src', 'blocked-uri' => 'inline']])
            )->assertNoContent();
        }

        // El login debe seguir disponible con sus 5 intentos intactos
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'separado-csp@wings.test',
            'password' => 'clave-correcta-123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
    }

    /**
     * 5. Contadores separados inverso: el bloqueo de login no bloquea los reportes CSP.
     */
    public function test_login_bloqueado_no_bloquea_reportes_csp(): void
    {
        $ip = '192.168.50.5';

        // 5 intentos fallidos bloquean el login
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
                'email' => 'bloqueo@wings.test',
                'password' => 'erronea',
            ])->assertRedirect();
        }

        // Login da 429
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route('login'), [
            'email' => 'bloqueo@wings.test',
            'password' => 'erronea',
        ])->assertTooManyRequests();

        // CSP sigue funcionando perfectamente recibiendo 204 No Content
        $this->call(
            'POST',
            '/csp-reporte',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/csp-report', 'REMOTE_ADDR' => $ip],
            json_encode(['csp-report' => ['document-uri' => 'https://wings.test/login', 'violated-directive' => 'script-src', 'blocked-uri' => 'inline']])
        )->assertNoContent();
    }

    /**
     * 6. Producción: RelojSimulado se ignora por completo en producción y nunca
     * se registra LimiteConRelojDetenido en el contenedor de Laravel.
     */
    public function test_en_produccion_reloj_simulado_se_ignora_y_no_se_registra_limite_con_reloj_detenido(): void
    {
        $antes = Carbon::now()->toDateTimeString();

        // Intentar aplicar en production
        $aplicada = RelojSimulado::aplicar('2026-12-05 10:00:00', 'production');
        $this->assertNull($aplicada, 'En producción RelojSimulado::aplicar() debe devolver null');
        $this->assertFalse(RelojSimulado::detenido(), 'En producción RelojSimulado::detenido() debe ser false');
        $this->assertSame($antes, Carbon::now()->toDateTimeString(), 'Carbon::now() no debe modificarse');

        // En producción el RateLimiter resuelto por el contenedor NUNCA es LimiteConRelojDetenido
        $limiter = app(RateLimiter::class);
        $this->assertNotInstanceOf(
            LimiteConRelojDetenido::class,
            $limiter,
            'En producción el RateLimiter debe ser la clase nativa de Laravel, nunca LimiteConRelojDetenido'
        );
    }

    /**
     * 7. Comportamiento de LimiteConRelojDetenido:
     * - Reloj detenido: bypass (tooManyAttempts devuelve false).
     * - Reloj avanzando: aplica el límite normalmente (tooManyAttempts devuelve true tras exceder).
     */
    public function test_limite_con_reloj_detenido_bypass_solo_si_esta_detenido(): void
    {
        $limiter = new LimiteConRelojDetenido(app('cache')->driver());

        // A. Reloj detenido (fijado sin timestamp de inicio o con reloj detenido)
        RelojSimulado::aplicar('2026-12-05 23:59:59', 'staging');
        $this->assertTrue(RelojSimulado::detenido());

        for ($i = 0; $i < 15; $i++) {
            $limiter->hit('key-bypass', 60);
        }
        $this->assertFalse(
            $limiter->tooManyAttempts('key-bypass', 5),
            'Con reloj detenido, tooManyAttempts debe devolver false para no trabar el sitio de pruebas'
        );

        // B. Reloj avanzando
        RelojSimulado::aplicar('2026-12-05 10:00:00', 'staging', time() - 10);
        $this->assertFalse(RelojSimulado::detenido());

        for ($i = 0; $i < 5; $i++) {
            $limiter->hit('key-normal', 60);
        }
        $this->assertTrue(
            $limiter->tooManyAttempts('key-normal', 5),
            'Con reloj avanzando, tooManyAttempts debe operar normalmente bloqueando al superar el umbral'
        );
    }
}
