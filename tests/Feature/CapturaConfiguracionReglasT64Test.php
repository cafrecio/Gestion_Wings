<?php

namespace Tests\Feature;

use App\Models\ReglaPrimerPago;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaConfiguracionReglasT64Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_html_para_capturas_t64(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);

        $admin = User::factory()->create([
            'rol'    => User::ROL_ADMIN,
            'activo' => true,
        ]);

        ReglaPrimerPago::query()->delete();
        $r1 = ReglaPrimerPago::create(['nombre' => 'Primera decena', 'dia_desde' => 1, 'dia_hasta' => 10, 'porcentaje' => 100, 'activo' => true]);
        $r2 = ReglaPrimerPago::create(['nombre' => 'Segunda decena', 'dia_desde' => 11, 'dia_hasta' => 20, 'porcentaje' => 70, 'activo' => true]);
        $r3 = ReglaPrimerPago::create(['nombre' => 'Fin de mes', 'dia_desde' => 21, 'dia_hasta' => 31, 'porcentaje' => 40, 'activo' => true]);

        $destinoDir = base_path('docs/06-pruebas/PRU-04/evidencia-t64');
        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0775, true);
        }

        $sanitizar = function (string $html): string {
            return str_replace([
                'src="http://gestion-wings/build/',
                'href="http://gestion-wings/build/',
            ], [
                'src="/build/',
                'href="/build/',
            ], $html);
        };

        $armarMarco375 = function (string $archivoHtml): string {
            return '<!doctype html><meta charset="utf-8">'."\n".
                   '<body style="margin:0;background:#888;display:flex;justify-content:center">'."\n".
                   '<iframe src="'.$archivoHtml.'" style="width:375px;height:1200px;border:0;background:#fff"></iframe></body>'."\n";
        };

        // 1. Estado normal del listado
        $htmlListado = $sanitizar($this->actingAs($admin)->get(route('web.configuraciones.index'))->assertOk()->getContent());
        file_put_contents($destinoDir.'/01-configuracion-reglas.html', $htmlListado);
        file_put_contents($destinoDir.'/01-configuracion-reglas-marco-375.html', $armarMarco375('01-configuracion-reglas.html'));

        // 2. Estado con edición inline abierta
        $htmlEdicion = str_replace(
            'id="panel-edit-'.$r2->id.'" style="display:none;',
            'id="panel-edit-'.$r2->id.'" style="display:block;',
            $htmlListado
        );
        // También si está con style="display:none; border-top...":
        $htmlEdicion = preg_replace(
            '/id="panel-edit-'.$r2->id.'"\s+style="display:none;/',
            'id="panel-edit-'.$r2->id.'" style="display:block;',
            $htmlEdicion
        );
        file_put_contents($destinoDir.'/02-configuracion-reglas-edicion.html', $htmlEdicion);
        file_put_contents($destinoDir.'/02-configuracion-reglas-edicion-marco-375.html', $armarMarco375('02-configuracion-reglas-edicion.html'));

        // 3. Estado con mensaje de error de hueco
        $mensajeError = 'Los días 11 a 15 quedan sin cubrir. Todos los días del 1 al 31 deben estar cubiertos por una regla.';
        $htmlError = str_replace(
            'id="panel-edit-'.$r1->id.'" style="display:none;',
            'id="panel-edit-'.$r1->id.'" style="display:block;',
            $htmlListado
        );
        $htmlError = preg_replace(
            '/id="panel-edit-'.$r1->id.'"\s+style="display:none;/',
            'id="panel-edit-'.$r1->id.'" style="display:block;',
            $htmlError
        );
        $htmlError = str_replace(
            'id="edit-error-'.$r1->id.'" style="display:none;',
            'id="edit-error-'.$r1->id.'" style="display:block;',
            $htmlError
        );
        $htmlError = preg_replace(
            '/id="edit-error-'.$r1->id.'"\s+style="display:none;/',
            'id="edit-error-'.$r1->id.'" style="display:block;',
            $htmlError
        );
        $htmlError = str_replace(
            'id="edit-error-'.$r1->id.'"></div>',
            'id="edit-error-'.$r1->id.'">'.$mensajeError.'</div>',
            $htmlError
        );
        file_put_contents($destinoDir.'/03-configuracion-reglas-error.html', $htmlError);
        file_put_contents($destinoDir.'/03-configuracion-reglas-error-marco-375.html', $armarMarco375('03-configuracion-reglas-error.html'));
    }
}
