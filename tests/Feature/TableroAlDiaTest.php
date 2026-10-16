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

    /**
     * Lo encontró Codex al verificar T3 (16/10): cambiar el paso de un defecto cerrado movía su
     * fecha de cierre a la del cambio, y la prueba de arriba no lo veía porque compara contra el
     * mismo generador.
     */
    public function test_cambiar_otro_dato_de_un_cerrado_no_mueve_su_fecha_de_cierre(): void
    {
        $tareas = tablero_leer();
        $a6 = array_column($tareas, null, 'id')['A6'];
        $this->assertSame('cerrado', $a6['estado'], 'El control necesita un defecto cerrado.');
        $md = file_get_contents(tablero_ruta(TABLERO_DEFECTOS));
        $html = file_get_contents(tablero_ruta(TABLERO_DEFECTOS_HTML));

        $despues = tablero_cambiar($tareas, 'A6', ['paso' => 'Un dato cualquiera'], '2030-01-01');

        $this->assertSame($a6['cerrado_el'], array_column($despues, null, 'id')['A6']['cerrado_el']);
        $this->assertSame($this->linea('/^### A6\..*$/m', $md), $this->linea('/^### A6\..*$/m', tablero_defectos_md($despues, $md)));
        $this->assertSame($this->linea('/^.*<input id="A6".*$/m', $html), $this->linea('/^.*<input id="A6".*$/m', tablero_defectos_html($despues, $html)));
    }

    public function test_al_cerrar_se_guarda_el_dia_del_cierre_y_no_cambia_despues(): void
    {
        $tareas = tablero_leer();
        $abierto = array_values(array_filter($tareas, fn ($t) => tablero_es_defecto($t) && $t['estado'] !== 'cerrado'))[0]
            ?? $this->markTestSkipped('No queda ningún defecto abierto para probar el cierre.');
        $id = $abierto['id'];
        $md = file_get_contents(tablero_ruta(TABLERO_DEFECTOS));

        $cerradas = tablero_cambiar($tareas, $id, ['estado' => 'cerrado', 'hizo' => 'Claude', 'verifica' => 'Codex'], '2030-01-02');
        $this->assertSame([], tablero_validar($cerradas));
        $this->assertSame('2030-01-02', array_column($cerradas, null, 'id')[$id]['cerrado_el']);
        $this->assertStringContainsString('CERRADO 02/01, verificó Codex', $this->linea("/^### {$id}\\..*$/m", tablero_defectos_md($cerradas, $md)));

        $despues = tablero_cambiar($cerradas, $id, ['paso' => 'Otro dato'], '2030-02-05');
        $this->assertSame('2030-01-02', array_column($despues, null, 'id')[$id]['cerrado_el']);
        $this->assertStringContainsString('CERRADO 02/01', $this->linea("/^### {$id}\\..*$/m", tablero_defectos_md($despues, $md)));

        $reabierta = tablero_cambiar($despues, $id, ['estado' => 'devuelto', 'tiene' => 'Claude'], '2030-02-06');
        $this->assertSame('', array_column($reabierta, null, 'id')[$id]['cerrado_el']);
        $this->assertStringNotContainsString('CERRADO', $this->linea("/^### {$id}\\..*$/m", tablero_defectos_md($reabierta, $md)));
    }

    /**
     * Segunda devolución de Codex (16/10): cerrar un defecto abierto guardaba bien, pero el
     * generador del HTML leía un dato que solo existe si la casilla ya estaba tildada y PHP
     * avisaba «Undefined array key 2». Acá cualquier aviso es una falla.
     */
    public function test_cerrar_un_defecto_abierto_marca_el_html_sin_avisos(): void
    {
        $tareas = tablero_leer();
        $abierto = array_values(array_filter($tareas, fn ($t) => tablero_es_defecto($t) && $t['estado'] !== 'cerrado'))[0]
            ?? $this->markTestSkipped('No queda ningún defecto abierto para probar el cierre.');
        $id = $abierto['id'];
        $html = file_get_contents(tablero_ruta(TABLERO_DEFECTOS_HTML));
        $this->assertStringNotContainsString(' checked', $this->linea("/^.*<input id=\"{$id}\".*$/m", $html));

        $cerradas = tablero_cambiar($tareas, $id, ['estado' => 'cerrado', 'hizo' => 'Claude', 'verifica' => 'Codex'], '2030-01-02');
        set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): never {
            throw new \ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });
        try {
            $renglon = $this->linea("/^.*<input id=\"{$id}\".*$/m", tablero_defectos_html($cerradas, $html));
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString("<input id=\"{$id}\" type=\"checkbox\" checked>", $renglon);
        $this->assertStringContainsString('<span class="sello">CERRADO 02/01, verificó Codex</span>', $renglon);
        $this->assertStringContainsString('<span class="tag done">Cerrado</span></div>', $renglon);
    }

    private function linea(string $patron, string $texto): string
    {
        $this->assertSame(1, preg_match($patron, $texto, $m), "No encuentro la línea {$patron}.");

        return $m[0];
    }

    private function sinRetornos(string $texto): string
    {
        return str_replace("\r\n", "\n", $texto);
    }
}
