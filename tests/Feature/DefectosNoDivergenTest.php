<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * La lista de defectos vive en dos archivos: el markdown que leemos los agentes y el
 * tablero HTML que abre Carlos. Tienen que tener los mismos defectos y los mismos estados.
 *
 * Existe porque el 05/10/2026 Carlos abrió la lista y dijo "está todo igual que hace diez
 * días". Tenía razón en lo que veía: 11 defectos ya cerrados figuraban abiertos en el
 * tablero, 7 existían solo ahí y 3 solo en el markdown. El trabajo estaba hecho; lo que
 * mentía era el documento, y sobre esa foto falsa se decide qué hacer después.
 *
 * El otro caso que cubre: A50 se resolvió el 04/10 con una regla en AGENTS.md y nadie lo
 * marcó. Se siguió hablando de él como pendiente al día siguiente.
 *
 * No extiende Tests\TestCase a propósito: no necesita la aplicación ni base de datos.
 */
class DefectosNoDivergenTest extends TestCase
{
    private const LISTA = 'docs/06-pruebas/PRU-02/DEFECTOS.md';
    private const TABLERO = 'docs/06-pruebas/PRU-02/DEFECTOS.html';

    public function test_los_dos_archivos_listan_los_mismos_defectos(): void
    {
        $enLista = array_keys($this->defectosDeLaLista());
        $enTablero = array_keys($this->defectosDelTablero());
        sort($enLista);
        sort($enTablero);

        $this->assertSame(
            [],
            array_values(array_diff($enLista, $enTablero)),
            "\nEstos defectos están en la lista y no en el tablero que abre Carlos:\n  "
            . implode(', ', array_diff($enLista, $enTablero))
            . "\n\nAgregarlos a " . self::TABLERO . "\n"
        );

        $this->assertSame(
            [],
            array_values(array_diff($enTablero, $enLista)),
            "\nEstos defectos están en el tablero y no en la lista:\n  "
            . implode(', ', array_diff($enTablero, $enLista))
            . "\n\nAgregarlos a " . self::LISTA . "\n"
        );
    }

    public function test_lo_cerrado_esta_cerrado_en_los_dos(): void
    {
        $lista = $this->defectosDeLaLista();
        $tablero = $this->defectosDelTablero();

        $distintos = [];
        foreach ($lista as $id => $cerradoEnLista) {
            if (isset($tablero[$id]) && $tablero[$id] !== $cerradoEnLista) {
                $distintos[] = $id . ($cerradoEnLista ? ' (cerrado en la lista, abierto en el tablero)' : ' (cerrado en el tablero, abierto en la lista)');
            }
        }

        $this->assertSame(
            [],
            $distintos,
            "\nEstos defectos dicen cosas distintas en cada archivo:\n  "
            . implode("\n  ", $distintos)
            . "\n\nUn defecto corregido y no marcado hace que se vuelva a hablar de él como pendiente.\n"
        );
    }

    public function test_el_avance_figura_arriba_de_los_dos(): void
    {
        $lista = $this->defectosDeLaLista();
        $cerrados = count(array_filter($lista));
        $total = count($lista);

        foreach ([self::LISTA, self::TABLERO] as $ruta) {
            $contenido = $this->leer($ruta);
            $this->assertMatchesRegularExpression(
                '/Avance al \d{2}\/\d{2}\/\d{4}: '.$cerrados.' cerrados de '.$total.'\b/',
                $contenido,
                "\n{$ruta} no dice el avance real arriba de todo: son {$cerrados} cerrados de {$total}.\n"
                ."Si hay que leer las fichas una por una para saber si avanzamos, el documento no sirve.\n"
            );
        }
    }

    /** @return array<string,bool> id => está cerrado */
    private function defectosDeLaLista(): array
    {
        preg_match_all('/^### ([AB]\d+)\.\s*(.*)$/m', $this->leer(self::LISTA), $m, PREG_SET_ORDER);

        $defectos = [];
        foreach ($m as [, $id, $resto]) {
            $defectos[$id] = str_contains(mb_strtoupper($resto), 'CERRADO');
        }

        return $defectos;
    }

    /** @return array<string,bool> id => está cerrado */
    private function defectosDelTablero(): array
    {
        $defectos = [];
        foreach (explode("\n", $this->leer(self::TABLERO)) as $linea) {
            if (preg_match('/<input id="([AB]\d+)"/', $linea, $m)) {
                $defectos[$m[1]] = str_contains($linea, 'tag done');
            }
        }

        return $defectos;
    }

    private function leer(string $ruta): string
    {
        $absoluta = dirname(__DIR__, 2) . '/' . $ruta;
        $this->assertFileExists($absoluta, "Falta {$ruta}. Si se movio, actualizar esta prueba.");

        return file_get_contents($absoluta);
    }
}
