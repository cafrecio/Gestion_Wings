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
    }
}
