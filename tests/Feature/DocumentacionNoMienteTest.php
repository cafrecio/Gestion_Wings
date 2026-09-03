<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

/**
 * Impide que la documentacion declare una cantidad de pruebas distinta de la real.
 *
 * Existe porque paso: ESTADO-ACTUAL.md declaraba 14 pruebas cuando habia 76, y sobre
 * esa informacion falsa se escribieron ordenes de trabajo equivocadas. La regla de
 * "actualizar el documento" dependia de que alguien se acordara. Esto no depende de
 * nadie: si el numero no coincide, la suite se pone en rojo.
 *
 * No extiende Tests\TestCase a proposito: no necesita la aplicacion ni base de datos.
 */
class DocumentacionNoMienteTest extends TestCase
{
    /**
     * Documentos que declaran la cantidad de pruebas y deben decir la verdad.
     *
     * @var array<string, string>
     */
    private const DOCUMENTOS = [
        // Anclados a la linea exacta: un patron suelto agarra otros numeros del
        // mismo archivo, como "268 pruebas GET" de la matriz de permisos.
        'docs/00-estado/ESTADO-ACTUAL.md'     => '/\*\*Tests\*\*.*?\*\*(\d+) pruebas/',
        'docs/00-estado/CHECKLIST-CARLOS.md'  => '/artisan test\s+#\s*(\d+) pruebas/',
        // Agregado el 01/09: declaraba 76 cuando habia 77 y nadie se entero,
        // porque el guardian no lo cubria.
        'docs/00-estado/PLAN-PRODUCCION.md'    => '/\| Suite \| \*\*(\d+) pruebas/',
    ];

    public function test_los_documentos_declaran_la_cantidad_real_de_pruebas(): void
    {
        $real = $this->contarMetodosDeTest();

        $this->assertGreaterThan(
            0,
            $real,
            'No se encontro ningun metodo de test. Revisar el contador, no la documentacion.'
        );

        foreach (self::DOCUMENTOS as $ruta => $patron) {
            $absoluta = $this->rutaBase().'/'.$ruta;

            $this->assertFileExists(
                $absoluta,
                "Falta {$ruta}. Si se movio, actualizar esta prueba."
            );

            $contenido = file_get_contents($absoluta);

            if (!preg_match($patron, $contenido, $coincidencia)) {
                $this->fail(
                    "{$ruta} ya no declara la cantidad de pruebas.\n".
                    "Deberia decir \"{$real} pruebas\". Si el formato cambio a proposito, ".
                    'actualizar el patron en '.self::class.'.'
                );
            }

            $declarado = (int) $coincidencia[1];

            $this->assertSame(
                $real,
                $declarado,
                "\n".
                "{$ruta} MIENTE.\n".
                "  Declara: {$declarado} pruebas\n".
                "  Reales:  {$real} pruebas\n\n".
                "Actualizar el documento en el mismo commit que agrego o quito la prueba.\n".
                "Un documento desactualizado hace que la proxima orden de trabajo se escriba\n".
                "sobre informacion falsa. Ya paso.\n"
            );
        }
    }

    /**
     * Cuenta los metodos de test declarados bajo tests/.
     *
     * Coincide con lo que reporta `php artisan test` mientras no se usen data
     * providers. Si alguno se agrega, este contador queda corto y hay que cambiarlo
     * por la salida real del corredor.
     */
    private function contarMetodosDeTest(): int
    {
        $total = 0;

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->rutaBase().'/tests')
        );

        foreach ($archivos as $archivo) {
            if (!$archivo->isFile() || !str_ends_with($archivo->getFilename(), 'Test.php')) {
                continue;
            }

            $total += preg_match_all(
                '/public function test[A-Za-z0-9_]*\s*\(/',
                file_get_contents($archivo->getPathname())
            );
        }

        return $total;
    }

    private function rutaBase(): string
    {
        return dirname(__DIR__, 2);
    }
}
