<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * La política de seguridad de contenido viaja en modo reporte: avisa en vez de
 * bloquear. Hasta el 13/09/2026 el encabezado no decía a dónde mandar los
 * avisos, así que el navegador los escribía en su propia consola y se perdían.
 *
 * Sin juntar esos avisos no hay forma de saber qué se rompería al pasar la
 * política a modo bloqueo, que es adonde apunta todo el trabajo de sacar el
 * JavaScript de las vistas.
 */
class CspReporteTest extends TestCase
{
    use RefreshDatabase;

    private function reporte(array $campos = []): array
    {
        return ['csp-report' => array_merge([
            'document-uri'        => 'https://wings.gestionar-te.com.ar/alumnos',
            'violated-directive'  => 'script-src',
            'effective-directive' => 'script-src',
            'blocked-uri'         => 'inline',
            'source-file'         => 'https://wings.gestionar-te.com.ar/alumnos',
            'line-number'         => 42,
        ], $campos)];
    }

    private function enviar(array $cuerpo)
    {
        return $this->call(
            'POST',
            '/csp-reporte',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            json_encode($cuerpo),
        );
    }

    public function test_la_politica_declara_a_donde_mandar_los_avisos(): void
    {
        $politica = $this->get('/login')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertNotNull($politica);
        $this->assertStringContainsString(
            'report-uri /csp-reporte',
            $politica,
            'Sin report-uri el modo reporte no recolecta nada y la CSP nunca se puede prender.',
        );
    }

    public function test_un_aviso_del_navegador_queda_registrado(): void
    {
        Log::shouldReceive('channel')->with('csp')->once()->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($mensaje, $datos) {
            return $mensaje === 'violacion'
                && $datos['directiva'] === 'script-src'
                && $datos['bloqueado'] === 'inline'
                && $datos['linea'] === 42;
        });

        $this->enviar($this->reporte())->assertNoContent();
    }

    public function test_no_exige_sesion_ni_token(): void
    {
        // El navegador manda el aviso por su cuenta: no tiene sesión iniciada
        // ni token CSRF. Si se los exigiéramos, no llegaría ninguno.
        Log::shouldReceive('channel')->with('csp')->andReturnSelf();
        Log::shouldReceive('info');

        $this->enviar($this->reporte())->assertNoContent();
        $this->assertGuest();
    }

    public function test_descarta_los_avisos_que_generan_las_extensiones_del_navegador(): void
    {
        // Son la mayor fuente de ruido de cualquier CSP: la extensión inyecta
        // su script en la página y el aviso no tiene nada que ver con Wings.
        Log::shouldReceive('channel')->never();

        foreach (['chrome-extension://abcd/inject.js', 'moz-extension://x/y.js', 'safari-extension://z'] as $origen) {
            $this->enviar($this->reporte(['blocked-uri' => $origen]))->assertNoContent();
        }
    }

    public function test_descarta_un_cuerpo_desmedido(): void
    {
        // La dirección es pública por necesidad: cualquiera puede llamarla.
        Log::shouldReceive('channel')->never();

        $this->enviar($this->reporte(['document-uri' => str_repeat('a', 9000)]))->assertNoContent();
    }

    public function test_descarta_lo_que_no_es_un_aviso_de_csp(): void
    {
        Log::shouldReceive('channel')->never();

        $this->enviar(['cualquier-cosa' => 'x'])->assertNoContent();
        $this->enviar(['csp-report' => 'no es un objeto'])->assertNoContent();
    }

    public function test_no_guarda_el_recorte_de_la_pagina(): void
    {
        // `script-sample` trae un pedazo del código de la pantalla, que puede
        // incluir el nombre o el DNI de un alumno. Queda fuera del registro a
        // propósito: para arreglar la violación alcanza con saber qué directiva
        // se violó y en qué archivo.
        Log::shouldReceive('channel')->with('csp')->once()->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($mensaje, $datos) {
            $volcado = json_encode($datos);

            return ! str_contains($volcado, 'script-sample')
                && ! str_contains($volcado, '40111222');
        });

        $this->enviar($this->reporte([
            'script-sample' => 'const dniAlumno = "40111222";',
        ]))->assertNoContent();
    }

    public function test_recorta_los_valores_demasiado_largos(): void
    {
        Log::shouldReceive('channel')->with('csp')->once()->andReturnSelf();
        Log::shouldReceive('info')->once()->withArgs(function ($mensaje, $datos) {
            return mb_strlen($datos['pagina']) <= 300;
        });

        $this->enviar($this->reporte(['document-uri' => 'https://wings/' . str_repeat('b', 2000)]))
            ->assertNoContent();
    }
}
