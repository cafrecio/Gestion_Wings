<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sexto_intento_fallido_en_un_minuto_responde_429(): void
    {
        User::factory()->create([
            'email' => 'login-throttle@wings.test',
            'password' => 'clave-correcta-segura',
            'activo' => true,
        ]);

        // Los avisos de seguridad que el navegador manda solo no gastan intentos de
        // ingreso. Compartían contador: en el sitio de prueba el segundo ingreso seguido
        // ya quedaba afuera (13/10/2026).
        for ($aviso = 1; $aviso <= 20; $aviso++) {
            $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/csp-report', 'REMOTE_ADDR' => '10.20.30.40'],
                json_encode(['csp-report' => ['document-uri' => 'https://wings.test/login', 'violated-directive' => 'script-src', 'blocked-uri' => 'inline']]))
                ->assertNoContent();
        }

        for ($intento = 1; $intento <= 5; $intento++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login'), [
                'email' => 'login-throttle@wings.test',
                'password' => 'clave-incorrecta',
            ])->assertRedirect();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])->post(route('login'), [
            'email' => 'login-throttle@wings.test',
            'password' => 'clave-incorrecta',
        ])->assertTooManyRequests();

        // Y al revés: con el ingreso bloqueado, los avisos se siguen recibiendo.
        $this->call('POST', '/csp-reporte', [], [], [], ['CONTENT_TYPE' => 'application/csp-report', 'REMOTE_ADDR' => '10.20.30.40'],
            json_encode(['csp-report' => ['document-uri' => 'https://wings.test/login', 'violated-directive' => 'script-src', 'blocked-uri' => 'inline']]))
            ->assertNoContent();
    }
}
