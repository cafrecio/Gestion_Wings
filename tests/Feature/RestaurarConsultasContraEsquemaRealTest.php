<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Las consultas de plata e invariantes de `scripts/servidor/restaurar.sh` se
 * ejecutan acá contra el esquema real, armado por las migraciones.
 *
 * Existe por lo que pasó el 22/09/2026, en el primer ensayo contra un respaldo de
 * verdad: la consulta de "deuda pendiente" sumaba `saldo_pendiente`, que **no es
 * una columna** —lo calcula el modelo— y en el servidor devolvió "NO SE PUDO
 * CONSULTAR". La prueba de shell del ensayo no lo vio nunca porque usa un `mysql`
 * simulado que responde lo que se le programa sin ejecutar SQL.
 *
 * Esta prueba lee las consultas del propio script, así que si alguien agrega una
 * que nombra una columna inexistente, falla acá y no en el servidor.
 */
class RestaurarConsultasContraEsquemaRealTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string,string> etiqueta => consulta */
    private function consultasDelScript(): array
    {
        $script = file_get_contents(base_path('scripts/servidor/restaurar.sh'));

        preg_match_all('/<<\'SQL\'\s*\n(.*?)\nSQL/s', $script, $bloques);
        $this->assertCount(2, $bloques[1], 'El script tenia que traer dos bloques de consultas: plata e invariantes.');

        $consultas = [];
        foreach ($bloques[1] as $bloque) {
            foreach (explode("\n", trim($bloque)) as $linea) {
                [$etiqueta, $sql] = explode('|', $linea, 2);
                $consultas[trim($etiqueta)] = trim($sql);
            }
        }

        return $consultas;
    }

    public function test_cada_consulta_del_ensayo_corre_contra_el_esquema_real(): void
    {
        $base = DB::connection()->getDatabaseName();
        $consultas = $this->consultasDelScript();

        $this->assertGreaterThanOrEqual(10, count($consultas));

        foreach ($consultas as $etiqueta => $sql) {
            try {
                $resultado = DB::select(str_replace('@', $base, $sql));
            } catch (\Throwable $e) {
                $this->fail("La consulta \"{$etiqueta}\" no corre contra el esquema real: " . $e->getMessage());
            }

            $this->assertNotEmpty($resultado, "La consulta \"{$etiqueta}\" no devolvio fila.");
        }
    }

    public function test_la_deuda_pendiente_se_calcula_igual_que_el_modelo(): void
    {
        // El modelo la calcula como max(0, original - pagado). Si el script usa
        // otra cuenta, compararia contra un numero que el sistema no muestra.
        $consultas = $this->consultasDelScript();

        $this->assertStringContainsString('GREATEST(monto_original - monto_pagado, 0)', $consultas['deuda pendiente']);
        $this->assertStringNotContainsString('saldo_pendiente', $consultas['deuda pendiente']);
    }
}
