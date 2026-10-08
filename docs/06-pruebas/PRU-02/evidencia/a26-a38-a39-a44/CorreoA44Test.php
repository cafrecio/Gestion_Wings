<?php

namespace Tests\Evidencia;

use App\Models\User;
use App\Notifications\AvisoOperativo;
use Tests\TestCase;

/**
 * Reproductor de evidencia, fuera de la suite permanente: guarda el correo de avisos tal
 * como lo arma Laravel, con la plantilla nueva y con la de fabrica, para compararlos.
 *
 *   php vendor/bin/phpunit docs/06-pruebas/PRU-02/evidencia/a26-a38-a39-a44/CorreoA44Test.php
 */
class CorreoA44Test extends TestCase
{
    public function test_guarda_el_correo_antes_y_despues(): void
    {
        // El caso que vio Carlos: un servidor con el nombre de fabrica.
        config(['app.name' => 'Laravel']);
        $aviso = new AvisoOperativo('Resumen diario', [
            'Cajas cerradas sin validar' => '2 ($58.000) — más vieja: Sandra Vidal (05/10/2026)',
            'Clases sin lista tomada' => '3 — la más vieja: Patín — Principiantes del 02/10/2026',
        ], 'Entrá a Wings para resolverlos.');
        $usuario = new User(['name' => 'Carlos', 'email' => 'carlos@example.test']);

        file_put_contents(__DIR__ . '/a44-correo-despues.html', (string) $aviso->toMail($usuario)->render());

        // Antes: se esconden las vistas propias para que Laravel use las suyas.
        $propias = resource_path('views/vendor/mail');
        rename($propias, $propias . '-oculta');
        try {
            app()->forgetInstance(\Illuminate\Mail\Markdown::class);
            app('view')->flushFinderCache();
            file_put_contents(__DIR__ . '/a44-correo-antes.html', (string) $aviso->toMail($usuario)->render());
        } finally {
            rename($propias . '-oculta', $propias);
        }

        $this->assertFileExists(__DIR__ . '/a44-correo-despues.html');
    }
}
