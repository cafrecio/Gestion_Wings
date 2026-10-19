<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AlumnoAutocompleteViewSecurityTest extends TestCase
{
    public function test_autocomplete_inserta_textos_de_base_sin_interpretarlos_como_html(): void
    {
        $raiz = dirname(__DIR__, 2);
        $view = file_get_contents($raiz . '/resources/views/alumnos/index.blade.php');
        // T73: el buscador salió de la vista a su módulo; la vista lo carga.
        $js = file_get_contents($raiz . '/resources/js/alumnos-index.js');

        $this->assertStringContainsString("@vite('resources/js/alumnos-index.js')", $view);
        $this->assertStringContainsString('label.textContent = r.label;', $js);
        $this->assertStringContainsString('sub.textContent = r.sub;', $js);
        $this->assertStringNotContainsString('li.innerHTML = `', $js);
    }
}
