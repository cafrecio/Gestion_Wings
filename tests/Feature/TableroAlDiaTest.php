<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * El estado de las tareas vive en un solo lugar: docs/00-estado/tareas.json. TABLERO.html,
 * QUIEN-HACE-QUE.md y el estado de cada defecto en DEFECTOS.md y DEFECTOS.html se generan
 * desde ahí con scripts/tablero.
 *
 * Existe por T3 (16/10/2026): hasta entonces el estado de un defecto se escribía a mano en
 * tres lugares. El 13/10, para pasar seis defectos a mejora futura o cerrarlos, hubo que
 * tocar los tres y el guardián que los comparaba falló dos veces en el mismo día.
 *
 * Si esta prueba falla: alguien editó a mano un archivo generado, o cambió tareas.json sin
 * regenerar. Se arregla con `php scripts/tablero/tablero.php generar`, nunca editando el HTML.
 *
 * No extiende Tests\TestCase a propósito: no necesita la aplicación ni base de datos.
 */
class TableroAlDiaTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/scripts/tablero/lib.php';
    }

    public function test_las_tareas_son_validas(): void
    {
        $this->assertSame([], tablero_validar(tablero_leer()), 'tareas.json tiene datos que el tablero no acepta.');
    }

    public function test_lo_generado_coincide_con_tareas_json(): void
    {
        $distintos = [];
        foreach (tablero_generados(tablero_leer()) as $ruta => $esperado) {
            $actual = file_get_contents(tablero_ruta($ruta));
            if ($this->sinRetornos($actual) !== $this->sinRetornos($esperado)) {
                $distintos[] = $ruta;
            }
        }

        $this->assertSame(
            [],
            $distintos,
            "\nEstos archivos no coinciden con tareas.json:\n  " . implode("\n  ", $distintos)
            . "\n\nSe editaron a mano o falta regenerar: php scripts/tablero/tablero.php generar\n"
        );
    }

    public function test_cada_defecto_del_tablero_esta_en_la_lista_y_al_reves(): void
    {
        $enTablero = array_column(array_filter(tablero_leer(), 'tablero_es_defecto'), 'id');
        preg_match_all('/^### ([AB]\d+)\./m', file_get_contents(tablero_ruta(TABLERO_DEFECTOS)), $m);
        sort($enTablero, SORT_NATURAL);
        $enLista = $m[1];
        sort($enLista, SORT_NATURAL);

        $this->assertSame($enTablero, $enLista, 'Un defecto está en el tablero y no en DEFECTOS.md, o al revés.');
    }

    private function sinRetornos(string $texto): string
    {
        return str_replace("\r\n", "\n", $texto);
    }
}
