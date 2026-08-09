<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AlumnoAutocompleteViewSecurityTest extends TestCase
{
    public function test_autocomplete_inserta_textos_de_base_sin_interpretarlos_como_html(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/alumnos/index.blade.php'
        );

        $this->assertStringContainsString('label.textContent = r.label;', $view);
        $this->assertStringContainsString('sub.textContent = r.sub;', $view);
        $this->assertStringNotContainsString('li.innerHTML = `', $view);
    }
}
