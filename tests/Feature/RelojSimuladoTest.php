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

    /**
     * Día 2 de PRU-04: con el reloj clavado, el límite de ingresos por minuto no se
     * limpiaba nunca y al quinto ingreso nadie más podía entrar al sitio de prueba.
     */
    public function test_con_el_momento_en_que_se_fijo_el_reloj_avanza_solo(): void
    {
        RelojSimulado::aplicar('2026-12-05 09:30:00', 'staging', time() - 90);

        $ahora = Carbon::now();
        $this->assertSame('2026-12-05', $ahora->toDateString());
        $this->assertGreaterThanOrEqual('09:31:30', $ahora->format('H:i:s'));
        $this->assertLessThan('09:32:00', $ahora->format('H:i:s'));

        // El límite de intentos vuelve a contar cuando pasa su minuto.
        $limite = app(\Illuminate\Cache\RateLimiter::class);
        $limite->hit('reloj-simulado', 60);
        $this->assertSame(1, $limite->attempts('reloj-simulado'));
        RelojSimulado::aplicar('2026-12-05 09:30:00', 'staging', time() - 90 - 61);
        $this->assertSame(0, $limite->attempts('reloj-simulado'));
    }

    public function test_el_reloj_que_avanza_no_cruza_la_medianoche_por_su_cuenta(): void
    {
        // Fijado a las 23:00 hace tres horas reales: se detiene en el último segundo del día.
        RelojSimulado::aplicar('2026-12-05 23:00:00', 'staging', time() - 3 * 3600);

        $this->assertSame('2026-12-05 23:59:59', Carbon::now()->toDateTimeString());
    }

    public function test_sin_ese_momento_o_si_es_futuro_el_reloj_queda_clavado(): void
    {
        foreach ([null, time() + 3600] as $desde) {
            RelojSimulado::aplicar('2026-12-05 09:30:00', 'staging', $desde);
            $this->assertSame('2026-12-05 09:30:00', Carbon::now()->toDateTimeString());
        }
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
