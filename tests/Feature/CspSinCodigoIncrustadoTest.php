<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La politica de seguridad de contenido (CSP) declara `script-src 'self'`: el
 * navegador solo ejecuta JavaScript que venga de un archivo propio, y rechaza todo
 * lo que este escrito dentro de la pagina.
 *
 * Hoy esa politica esta en modo reporte (`Content-Security-Policy-Report-Only`), o
 * sea que avisa pero no bloquea. Mientras queden vistas con codigo incrustado no se
 * puede pasar a modo bloqueo, porque esas pantallas dejarian de funcionar.
 *
 * Esta prueba no arregla nada: **fija el numero y no lo deja crecer**. Cada vez que
 * se saca codigo de una vista hay que bajar el numero de aca, igual que se hace con
 * la cantidad de pruebas en la documentacion.
 */
class CspSinCodigoIncrustadoTest extends TestCase
{
    /**
     * Bloques <script> escritos dentro de una vista, sin `src`.
     *
     * Medido el 06/09/2026 en 24 archivos.
     */
    private const BLOQUES_SCRIPT_PERMITIDOS = 26;

    /**
     * Atributos de evento escritos en el HTML: onclick, onsubmit, onchange y demas.
     *
     * Eran 40 el 06/09/2026. Bajaron a 34 ese mismo dia al reemplazar los seis
     * `onchange="this.form.submit()"` de los filtros por `data-enviar-al-cambiar`,
     * que resuelve `ds-app.js` para todo el sistema.
     *
     * Bajaron a 24 al mover los diez efectos de mouse (dashboard y grupos/show) a
     * `data-elevar` y `data-hover-fondo`, tambien en `ds-app.js`. Si esos fallaran,
     * el peor caso es que una tarjeta no se levante al pasar el mouse.
     *
     * Bajaron a 10 (SEG-11, 13/09/2026) al mover los 14 `onclick` de las vistas
     * (alumnos/show, caja/detalle, caja/resumen, liquidaciones/create, revision-cobranza/index)
     * a atributos data-* delegados en `ds-app.js`.
     *
     * Los 10 que quedan son exclusivamente `onsubmit="return confirm(...)"` que protegen
     * eliminaciones: no se tocan hasta poder comprobarlos con un clic real, porque si
     * se mueven a JavaScript y el archivo no carga, el borrado se ejecutaria sin preguntar.
     */
    private const MANEJADORES_PERMITIDOS = 10;

    public function test_no_crece_la_cantidad_de_bloques_de_codigo_incrustado(): void
    {
        [$total, $porArchivo] = $this->contar('/<script(?:\s[^>]*)?>/i', excluirConSrc: true);

        $this->assertSame(
            self::BLOQUES_SCRIPT_PERMITIDOS,
            $total,
            $this->explicar(
                'bloques <script> incrustados',
                self::BLOQUES_SCRIPT_PERMITIDOS,
                $total,
                $porArchivo
            )
        );
    }

    public function test_no_crece_la_cantidad_de_manejadores_escritos_en_el_html(): void
    {
        [$total, $porArchivo] = $this->contar('/\son[a-z]+=/i');

        $this->assertSame(
            self::MANEJADORES_PERMITIDOS,
            $total,
            $this->explicar(
                'manejadores on...= en el HTML',
                self::MANEJADORES_PERMITIDOS,
                $total,
                $porArchivo
            )
        );
    }

    /**
     * @return array{0:int, 1:array<string,int>}
     */
    private function contar(string $patron, bool $excluirConSrc = false): array
    {
        $total      = 0;
        $porArchivo = [];

        foreach ($this->vistas() as $ruta) {
            $contenido = file_get_contents($ruta);

            preg_match_all($patron, $contenido, $coincidencias);
            $encontradas = $coincidencias[0];

            if ($excluirConSrc) {
                $encontradas = array_filter($encontradas, fn($tag) => !str_contains($tag, 'src='));
            }

            if ($encontradas === []) {
                continue;
            }

            $relativa              = str_replace('\\', '/', substr($ruta, strlen(resource_path('views')) + 1));
            $porArchivo[$relativa] = count($encontradas);
            $total                += count($encontradas);
        }

        arsort($porArchivo);

        return [$total, $porArchivo];
    }

    /**
     * @return list<string>
     */
    private function vistas(): array
    {
        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        $vistas = [];

        foreach ($iterador as $archivo) {
            if ($archivo->isFile() && str_ends_with($archivo->getFilename(), '.blade.php')) {
                $vistas[] = $archivo->getPathname();
            }
        }

        sort($vistas);

        return $vistas;
    }

    /**
     * @param array<string,int> $porArchivo
     */
    private function explicar(string $que, int $esperado, int $real, array $porArchivo): string
    {
        $detalle = '';
        foreach ($porArchivo as $archivo => $cuantos) {
            $detalle .= "\n  {$cuantos}  {$archivo}";
        }

        if ($real > $esperado) {
            return "Aparecieron {$que} nuevos: habia {$esperado} y ahora hay {$real}.\n".
                'No se puede sumar codigo incrustado a una vista: mientras existan, la '.
                "politica de seguridad no puede pasar a modo bloqueo. Ponelo en un archivo\n".
                "de resources/js/ e importalo desde app.js, como se hizo con ds-app.js.\n".
                "Reparto actual:{$detalle}";
        }

        return "Bajaron los {$que}: habia {$esperado} y ahora hay {$real}. Es una buena\n".
            "noticia, pero hay que dejarla escrita: actualiza la constante de esta prueba\n".
            "para que el numero nuevo quede protegido.\n".
            "Reparto actual:{$detalle}";
    }
}
