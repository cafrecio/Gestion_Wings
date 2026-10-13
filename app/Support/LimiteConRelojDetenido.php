<?php

namespace App\Support;

use Illuminate\Cache\RateLimiter;

/**
 * El límite de intentos, para el sitio de prueba con reloj simulado.
 *
 * Un límite «5 por minuto» necesita que el minuto pase. Con el reloj simulado parado
 * no pasa nunca: al quinto ingreso nadie más podía entrar, y así se frenó la prueba
 * el 10/10 y otra vez el 12/10 (T32).
 *
 * Mientras el reloj simulado avanza, el límite funciona igual que siempre. Solo
 * cuando está parado deja de aplicarse. En producción esta clase no se usa: la
 * registra AppServiceProvider únicamente si hay una fecha simulada aplicada, y
 * RelojSimulado nunca la aplica en producción.
 */
class LimiteConRelojDetenido extends RateLimiter
{
    public function tooManyAttempts($key, $maxAttempts)
    {
        if (RelojSimulado::detenido()) {
            return false;
        }

        return parent::tooManyAttempts($key, $maxAttempts);
    }
}
