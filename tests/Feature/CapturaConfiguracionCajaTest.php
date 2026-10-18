<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\CajaOperativa;
use App\Models\Deporte;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Carbon\Carbon;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapturaConfiguracionCajaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_html_para_capturas(): void
    {
        if (getenv('WINGS_CAPTURAS') !== '1') {
            $this->markTestSkipped('Solo se corre a pedido, con WINGS_CAPTURAS=1.');
        }

        $this->seed(CatalogosSeeder::class);
        Carbon::setTestNow('2026-10-18 10:00:00');

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['rol' => User::ROL_OPERATIVO, 'activo' => true]);

        $tipo1 = TipoCaja::firstOrCreate(['nombre' => 'Efectivo Mostrador'], ['abreviatura' => 'EFT', 'activo' => true]);
        $tipo2 = TipoCaja::firstOrCreate(['nombre' => 'Caja Fuerte / Alternativo'], ['abreviatura' => 'CFA', 'activo' => true]);

        $cajaService = app(CajaService::class);
        $cajaService->configurarMostrador($tipo1->id, $admin->id);

        $destinoDir = base_path('docs/06-pruebas/PRU-04/evidencia-t57');
        if (!is_dir($destinoDir)) {
            mkdir($destinoDir, 0775, true);
        }

        // 1. Estado sin caja abierta: Habilitada
        $htmlHabilitada = $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent();
        $htmlHabilitada = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlHabilitada);
        file_put_contents($destinoDir.'/caja-configuracion-habilitada.html', $htmlHabilitada);

        // 2. Estado con caja abierta: Deshabilitada
        CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'tipo_caja_efectivo_id' => $tipo1->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 1000,
            'apertura_at' => now(),
        ]);

        $htmlDeshabilitada = $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent();
        $htmlDeshabilitada = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlDeshabilitada);
        file_put_contents($destinoDir.'/caja-configuracion-deshabilitada.html', $htmlDeshabilitada);

        // 3. Aviso al intentar desactivar el medio de pago del cajón en tipos-caja/index
        $resTipo = $this->actingAs($admin)->from(route('web.tipos-caja.index'))
            ->patch(route('web.tipos-caja.toggle-activo', $tipo1->id));
        $resTipo->assertRedirect(route('web.tipos-caja.index'))->assertSessionHas('error');
        $htmlAvisoTipo = $this->actingAs($admin)->get(route('web.tipos-caja.index'))->assertOk()->getContent();
        $htmlAvisoTipo = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlAvisoTipo);
        file_put_contents($destinoDir.'/tipo-caja-cajon-aviso.html', $htmlAvisoTipo);

        // 4. Aviso al intentar desactivar un grupo en uso en grupos/index
        $deporte = Deporte::first() ?? Deporte::create(['nombre' => 'Patin', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
        $nivel = Nivel::first() ?? Nivel::create(['nombre' => 'Inicial']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id], ['activo' => true]);
        Alumno::create([
            'nombre' => 'Alumno',
            'apellido' => 'Activo',
            'dni' => '99887766',
            'fecha_nacimiento' => '2005-01-01',
            'fecha_alta' => today()->toDateString(),
            'celular' => '1122334455',
            'deporte_id' => $deporte->id,
            'grupo_id' => $grupo->id,
            'activo' => true,
        ]);
        $resGrupo = $this->actingAs($admin)->from(route('web.grupos.index'))
            ->patch(route('web.grupos.toggle-activo', $grupo->id));
        $resGrupo->assertRedirect(route('web.grupos.index'))->assertSessionHas('error');
        $htmlAvisoGrupo = $this->actingAs($admin)->get(route('web.grupos.index'))->assertOk()->getContent();
        $htmlAvisoGrupo = str_replace(['src="/build/', 'href="/build/'], [
            'src="'.str_replace('\\', '/', public_path('build')).'/',
            'href="'.str_replace('\\', '/', public_path('build')).'/',
        ], $htmlAvisoGrupo);
        file_put_contents($destinoDir.'/grupo-en-uso-aviso.html', $htmlAvisoGrupo);

        Carbon::setTestNow();
    }
}
