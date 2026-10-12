<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Configuracion;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Nivel;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Evidencia, no prueba permanente: le pide a la aplicación el buscador de Cobrar con
 * tres alumnas que deben y dos al día, y guarda el HTML que devuelve.
 * Las imágenes se sacan después con tomar-capturas.mjs.
 */
class CapturaT29Test extends TestCase
{
    use RefreshDatabase;

    public function test_generar_htmls_para_capturas(): void
    {
        $this->seed(CatalogosSeeder::class);
        Configuracion::set('dias_gracia_cobranza', 10);
        Carbon::setTestNow('2026-10-12 10:00:00');

        $sandra = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true, 'name' => 'Sandra Mostrador']);
        \Tests\Support\CajaDeclarada::crear($sandra->id, TipoCaja::firstOrFail()->id);

        $patin = Deporte::create(['nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
        $nivel = Nivel::firstOrCreate(['nombre' => 'Principiantes']);
        $grupo = Grupo::create(['deporte_id' => $patin->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan  = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true]);

        $alta = function (string $apellido, string $nombre, array $periodosImpagos) use ($patin, $grupo, $plan) {
            $alumno = Alumno::create([
                'apellido' => $apellido, 'nombre' => $nombre, 'dni' => (string) (32000000 + Alumno::count() + 1),
                'deporte_id' => $patin->id, 'grupo_id' => $grupo->id, 'fecha_nacimiento' => '2012-03-01',
                'celular' => '11-4000-0000', 'fecha_alta' => '2026-08-01', 'activo' => true,
            ]);
            AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-08-01', 'activo' => true]);
            foreach ($periodosImpagos as $periodo) {
                DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => $periodo, 'monto_original' => 48000,
                    'monto_pagado' => 0, 'monto_condonado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE]);
            }
        };

        $alta('Cabrera', 'Renata', ['2026-10']);
        $alta('Fernández', 'Emma', ['2026-09', '2026-10']);
        $alta('Rodríguez', 'Mateo', ['2026-10']);
        $alta('Navarro', 'Julián', []);
        $alta('Gómez', 'Lucas', []);

        $this->guardar('sin-buscar', $this->actingAs($sandra)->get(route('web.caja.cobrar-cuota'))->assertOk()->getContent());
        $this->guardar('buscando-a-quien-no-debe', $this->actingAs($sandra)->get(route('web.caja.cobrar-cuota', ['search' => 'Navarro']))->assertOk()->getContent());
        $this->guardar('busqueda-sin-resultados', $this->actingAs($sandra)->get(route('web.caja.cobrar-cuota', ['search' => 'Zzz']))->assertOk()->getContent());

        DeudaCuota::query()->update(['monto_pagado' => 48000, 'estado' => DeudaCuota::ESTADO_PAGADA]);
        $this->guardar('nadie-debe', $this->actingAs($sandra)->get(route('web.caja.cobrar-cuota'))->assertOk()->getContent());

        auth()->logout();
        $this->guardar('login-control', $this->get(route('login'))->assertOk()->getContent());
    }

    private function guardar(string $nombre, string $html): void
    {
        $dir = base_path('docs/06-pruebas/PRU-04/evidencia-t29');
        $build = str_replace(chr(92), '/', public_path('build'));
        $html = str_replace(['src="/build/', 'href="/build/'], ['src="'.$build.'/', 'href="'.$build.'/'], $html);
        file_put_contents("{$dir}/{$nombre}.html", $html);
        file_put_contents("{$dir}/{$nombre}-marco-360.html",
            '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Wings · '.$nombre.' (360px)</title></head>'
            .'<body style="margin:0;background:#888;display:flex;justify-content:center;padding:24px 0;">'
            .'<iframe src="'.$nombre.'.html" style="width:360px;height:1150px;border:0;background:#fff;box-shadow:0 8px 30px rgba(0,0,0,0.35);border-radius:16px;"></iframe>'
            .'</body></html>');
    }
}
