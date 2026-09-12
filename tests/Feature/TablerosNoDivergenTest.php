<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * El plan de trabajo vive en dos archivos: el HTML con casillas que abre Carlos y
 * el markdown que leemos los agentes. Tienen que listar las mismas tareas y
 * coincidir en cuales estan cerradas. El detalle puede diferir — el de los agentes
 * lleva criterios de aceptacion y evidencia — pero el indice y el avance, no.
 *
 * Existe porque paso dos veces en dos dias: FIN-03 y FIN-05 el 11/09, SEG-01 el
 * 12/09. Las tres estaban cerradas para los agentes y sin tildar para Carlos, que
 * las descubrio mirando la pantalla. Y ENT-09 existia solo en el tablero de Carlos.
 *
 * No extiende Tests\TestCase a proposito: no necesita la aplicacion ni base de datos.
 */
class TablerosNoDivergenTest extends TestCase
{
    private const TABLERO_CARLOS = 'docs/07-evaluacion/PLAN-TRABAJO-CARLOS-v2026-09-08.html';
    private const PLAN_AGENTES   = 'docs/07-evaluacion/PLAN-TRABAJO-IA-v2026-09-08.md';

    /**
     * Palabras con las que el plan declara que una tarea ya no esta abierta.
     *
     * PAUSADA no entra: una tarea pausada sigue abierta y su casilla tiene que
     * quedar vacia. Es el caso de FDS-03.
     */
    private const MARCAS_DE_CIERRE = [
        'VERIFICADA', 'CERRADA', 'CERRADO', 'CORREGIDA', 'IMPLEMENTADA',
        'NO APLICA', 'Cerrada',
    ];

    public function test_los_dos_tableros_listan_las_mismas_tareas(): void
    {
        $enCarlos = array_keys($this->casillasDeCarlos());
        $enPlan   = $this->tareasDelPlan();

        sort($enCarlos);
        sort($enPlan);

        $faltanEnPlan   = array_diff($enCarlos, $enPlan);
        $faltanEnCarlos = array_diff($enPlan, $enCarlos);

        $this->assertSame(
            [],
            array_values($faltanEnPlan),
            "\nEstas tareas estan en el tablero de Carlos y no en el plan de los agentes:\n  "
            . implode(', ', $faltanEnPlan)
            . "\n\nUn agente que busque ese ID no lo encuentra. Agregarlo a "
            . self::PLAN_AGENTES . "\n"
        );

        $this->assertSame(
            [],
            array_values($faltanEnCarlos),
            "\nEstas tareas estan en el plan de los agentes y no en el tablero de Carlos:\n  "
            . implode(', ', $faltanEnCarlos)
            . "\n\nCarlos no las ve. Agregarlas a " . self::TABLERO_CARLOS . "\n"
        );
    }

    public function test_lo_cerrado_para_los_agentes_esta_tildado_para_carlos(): void
    {
        $casillas = $this->casillasDeCarlos();
        $cerradas = $this->tareasCerradasEnElPlan();

        $sinTildar = [];
        foreach ($cerradas as $id => $linea) {
            if (isset($casillas[$id]) && $casillas[$id] === false) {
                $sinTildar[$id] = $linea;
            }
        }

        $detalle = '';
        foreach ($sinTildar as $id => $linea) {
            $detalle .= "\n  {$id} — el plan dice: " . mb_substr($linea, 0, 110);
        }

        $this->assertSame(
            [],
            array_keys($sinTildar),
            "\nEstas tareas figuran cerradas en el plan de los agentes y siguen SIN TILDAR\n"
            . "en el tablero que abre Carlos:\n" . $detalle
            . "\n\nSon dos archivos: marcar una tarea exige tocar los dos. En el HTML hay que\n"
            . "agregar `checked` a la casilla, no alcanza con cambiar el texto.\n"
            . "Ya paso con FIN-03 y FIN-05 el 11/09 y con SEG-01 el 12/09: las tres estaban\n"
            . "cerradas para los agentes y abiertas para Carlos.\n"
        );
    }

    // ── Lectura de los dos archivos ──────────────────────────────────────

    /** @return array<string,bool> id => si la casilla esta tildada */
    private function casillasDeCarlos(): array
    {
        $html = $this->leer(self::TABLERO_CARLOS);

        preg_match_all(
            '/<input id="([A-Z]+-\d+)" type="checkbox"( checked)?>/',
            $html,
            $coincidencias,
            PREG_SET_ORDER
        );

        $this->assertNotEmpty(
            $coincidencias,
            'No se encontro ninguna casilla en ' . self::TABLERO_CARLOS
            . '. Si el formato del tablero cambio, actualizar esta prueba.'
        );

        $casillas = [];
        foreach ($coincidencias as $c) {
            $casillas[$c[1]] = ! empty($c[2]);
        }

        return $casillas;
    }

    /** @return list<string> */
    private function tareasDelPlan(): array
    {
        preg_match_all('/\b([A-Z]{3}-\d{2})\b/', $this->leer(self::PLAN_AGENTES), $m);

        return array_values(array_unique($m[1]));
    }

    /** @return array<string,string> id => la linea que lo declara cerrado */
    private function tareasCerradasEnElPlan(): array
    {
        $cerradas = [];

        foreach (explode("\n", $this->leer(self::PLAN_AGENTES)) as $linea) {
            if (! preg_match('/\b([A-Z]{3}-\d{2})\b/', $linea, $id)) {
                continue;
            }

            foreach (self::MARCAS_DE_CIERRE as $marca) {
                if (str_contains($linea, $marca)) {
                    $cerradas[$id[1]] = trim(preg_replace('/\s+/', ' ', $linea));
                    break;
                }
            }
        }

        return $cerradas;
    }

    private function leer(string $ruta): string
    {
        $absoluta = dirname(__DIR__, 2) . '/' . $ruta;

        $this->assertFileExists(
            $absoluta,
            "Falta {$ruta}. Si el plan se renombro o se movio, actualizar esta prueba."
        );

        return file_get_contents($absoluta);
    }
}
