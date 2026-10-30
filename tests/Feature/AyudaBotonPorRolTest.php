<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * T13 etapa C (Claude, 30/10/2026): el ? de cada pantalla abre una página que el
 * rol que mira puede leer. En la etapa A, el ? de Clases llevaba a una página de la
 * operativa y a la profesora le respondía 403.
 */
class AyudaBotonPorRolTest extends TestCase
{
    use RefreshDatabase;

    private const ENLACE = '#<a href="([^"]+)"\s+class="[^"]*"\s+title="Ayuda sobre esta pantalla"#';

    public function test_el_boton_de_ayuda_abre_una_pagina_que_el_rol_puede_leer(): void
    {
        $fallas = [];
        $revisadas = 0;

        foreach (['ADMIN', 'OPERATIVO', 'PROFESOR'] as $rol) {
            $usuario = User::factory()->create(['rol' => $rol, 'activo' => true]);

            foreach ($this->pantallasSinParametros() as $uri) {
                $respuesta = $this->actingAs($usuario)->get('/' . $uri);
                if ($respuesta->getStatusCode() !== 200) continue;
                if (!preg_match(self::ENLACE, (string) $respuesta->getContent(), $m)) continue;

                $revisadas++;
                $ayuda = $this->actingAs($usuario)->get(html_entity_decode($m[1]));
                if ($ayuda->getStatusCode() !== 200) {
                    $fallas[] = "$rol en /$uri: el ? lleva a {$m[1]} y responde {$ayuda->getStatusCode()}";
                }
            }
        }

        $this->assertGreaterThan(0, $revisadas, 'Ninguna pantalla mostró el botón de ayuda.');
        $this->assertSame([], $fallas, implode("\n", $fallas));
    }

    public function test_la_profesora_tiene_ayuda_en_clases_y_abre_su_propia_pagina(): void
    {
        $profesora = User::factory()->create(['rol' => 'PROFESOR', 'activo' => true]);

        $clases = $this->actingAs($profesora)->get(route('web.clases.index'))->assertOk();
        $this->assertMatchesRegularExpression(self::ENLACE, (string) $clases->getContent());
        preg_match(self::ENLACE, (string) $clases->getContent(), $m);

        $this->assertStringEndsWith('/ayuda/consultar-mis-clases', html_entity_decode($m[1]));
        $this->actingAs($profesora)->get(html_entity_decode($m[1]))->assertOk()->assertSee('Ver mis clases de hoy y de otros días');
    }

    public function test_cada_pagina_nombrada_por_un_boton_de_ayuda_existe(): void
    {
        $faltan = [];
        foreach (File::allFiles(resource_path('views')) as $vista) {
            preg_match_all("/@section\('ayuda-slug',\s*(.+)\)\s*$/m", $vista->getContents(), $secciones);
            foreach ($secciones[1] as $expresion) {
                preg_match_all("/'([a-z0-9-]+)'/", $expresion, $slugs);
                foreach ($slugs[1] as $slug) {
                    if (!glob(resource_path("manual/*/$slug.md"))) {
                        $faltan[] = $vista->getRelativePathname() . ": $slug";
                    }
                }
            }
        }

        $this->assertSame([], $faltan, implode("\n", $faltan));
    }

    /** Pantallas de la web que se abren sin datos: listados, altas e inicios. */
    private function pantallasSinParametros(): array
    {
        $excluir = '#^(api|ayuda|login|logout|csp|up$|sanctum|storage)|check|autocomplete|preview|exportar|descargar|plantilla|pdf|json#';

        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => in_array('GET', $ruta->methods(), true))
            ->filter(fn ($ruta) => !str_contains($ruta->uri(), '{'))
            ->filter(fn ($ruta) => in_array('web', $ruta->gatherMiddleware(), true))
            ->map(fn ($ruta) => $ruta->uri())
            ->reject(fn ($uri) => preg_match($excluir, $uri))
            ->unique()->values()->all();
    }
}
