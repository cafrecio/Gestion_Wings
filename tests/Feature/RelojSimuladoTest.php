<?php

namespace Tests\Feature;

use App\Support\RelojSimulado;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * El reloj simulado existe para PRU-02: recorrer meses de club en unas horas.
 *
 * La prueba que importa es la segunda: **en producción no se aplica nunca**. Si
 * alguna vez esa clave se copia por error al `.env` de wings, la plata del club se
 * calcularía contra un día inventado —qué mes se factura, quién es moroso, qué caja
 * está vieja—. Por eso el bloqueo no es de configuración: está en el código.
 */
class RelojSimuladoTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_en_el_servidor_de_prueba_wings_cree_que_es_el_dia_indicado(): void
    {
        $aplicada = RelojSimulado::aplicar('2026-12-05 09:30:00', 'staging');

        $this->assertSame('2026-12-05 09:30:00', $aplicada);
        $this->assertSame('2026-12-05', Carbon::now()->toDateString());
        $this->assertSame('2026-12-05', now()->toDateString(), 'El helper now() es el que usa toda la aplicación.');
    }

    public function test_en_produccion_se_ignora_aunque_este_escrita(): void
    {
        $antes = Carbon::now()->toDateString();

        $aplicada = RelojSimulado::aplicar('2026-12-05', 'production');

        $this->assertNull($aplicada);
        $this->assertSame($antes, Carbon::now()->toDateString(), 'En produccion la fecha tiene que seguir siendo la real.');
        $this->assertFalse(Carbon::hasTestNow());
    }

    public function test_sin_valor_o_con_una_fecha_invalida_no_toca_el_reloj(): void
    {
        foreach ([null, '', '   ', 'mañana', '2026-13-45'] as $valor) {
            $this->assertNull(RelojSimulado::aplicar($valor, 'staging'), var_export($valor, true));
            $this->assertFalse(Carbon::hasTestNow(), var_export($valor, true));
        }
    }

    public function test_el_comando_no_cambia_nada_en_produccion(): void
    {
        app()['env'] = 'production';

        $this->artisan('wings:fecha-simulada', ['fecha' => '2026-12-05'])
            ->expectsOutputToContain('En producción Wings usa siempre la fecha real')
            ->assertFailed();
    }
}
