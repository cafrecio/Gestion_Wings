<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
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
 * Deja en disco el HTML de la ficha con el botón Anular, para sacarle la captura que exige
 * `AGENTS.md` §1 antes de pedirle a Carlos la autorización de diseño.
 *
 * No es una prueba de comportamiento: produce evidencia. Se salta sola si no se pide
 * explícitamente con WINGS_CAPTURAS=1, para no escribir archivos en cada corrida.
 */
class CapturaFichaAnularTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_el_html_de_la_ficha_con_el_boton_anular(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-05 10:00:00');

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $tipoCaja = TipoCaja::first() ?? TipoCaja::create(['nombre' => 'Efectivo', 'activo' => true]);
        $deporte = Deporte::first() ?? Deporte::create([
            'nombre' => 'Patín', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true,
        ]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id, 'activo' => true]);
        $plan = GrupoPlan::create([
            'grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 48000, 'activo' => true,
        ]);

        $alumno = Alumno::create([
            'apellido' => 'Gómez', 'nombre' => 'Luz', 'dni' => '31222333',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id,
            'fecha_nacimiento' => '2005-01-01', 'celular' => '11-4000-0000',
            'fecha_alta' => '2026-09-01', 'activo' => true,
        ]);
        AlumnoPlan::create([
            'alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-09-01', 'activo' => true,
        ]);
        DeudaCuota::create([
            'alumno_id' => $alumno->id, 'periodo' => '2026-10', 'monto_original' => 48000,
            'monto_pagado' => 0, 'estado' => DeudaCuota::ESTADO_PENDIENTE,
        ]);

        // Un cobro del dueño, que es el que se puede anular desde acá.
        $this->actingAs($admin)->post(route('web.caja.pagar', $alumno->id), [
            'tipo_caja_id' => $tipoCaja->id,
            'periodos' => ['2026-10'],
            'montos_cuota' => ['2026-10' => 48000],
            'fecha_pago' => '2026-10-05',
        ])->assertSessionHas('success');

        $html = $this->actingAs($admin)->get(route('web.alumnos.show', $alumno->id))->assertOk()->getContent();

        // Los estilos compilados se referencian desde la raíz del sitio; para abrir el
        // archivo suelto en el navegador se apuntan al disco.
        $html = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $html);

        $destino = base_path('docs/06-pruebas/PRU-02/capturas-a13/ficha-anular.html');
        if (!is_dir(dirname($destino))) {
            mkdir(dirname($destino), 0775, true);
        }
        file_put_contents($destino, $html);

        $this->assertStringContainsString('data-abrir-anular', $html);
        Carbon::setTestNow();
    }
}
