<?php

namespace App\Support;

/**
 * Solo para la prueba por días en el sitio de prueba (Carlos, 28/10/2026): las confirmaciones
 * nativas del navegador traban al Chrome automático de Codex. Con PRUEBA_SIN_CONFIRMACIONES=true
 * el layout marca la página y resources/js/prueba-sin-confirmaciones.js las acepta solas.
 * En producción nunca se activa, aunque la variable esté escrita.
 */
class PruebaSinConfirmaciones
{
    public static function activa(): bool
    {
        return (bool) config('app.prueba_sin_confirmaciones') && !app()->environment('production');
    }
}
