<?php

namespace Tests\Feature;

use App\Models\CajaOperativa;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guarda el HTML real que Wings devuelve en Caja para el dueño (T96).
 * Se copia a tests/Feature solo para correrlo, con WINGS_CAPTURAS=1; no queda en la suite.
 */
class CapturasT96Test extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_html_de_caja(): void
    {
        $this->seed(CatalogosSeeder::class);

        $admin = User::factory()->create(['name' => 'Laura Gómez', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['name' => 'Sandra Ríos', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['abreviatura' => 'EFE', 'activo' => true]);

        $destino = base_path('docs/06-pruebas/PRU-04/evidencia-t96/html');
        if (!is_dir($destino)) {
            mkdir($destino, 0775, true);
        }
        $build = 'file:///'.str_replace('\\', '/', public_path('build'));
        $guardar = function (string $nombre) use ($admin, $destino, $build) {
            $html = $this->actingAs($admin)->get(route('web.caja.index'))->assertOk()->getContent();
            $html = preg_replace('#(src|href)="(https?://[^"/]+)?/build/#', '$1="'.$build.'/', $html);
            file_put_contents($destino.'/'.$nombre.'.html', $html);
        };

        $guardar('caja-sin-configurar');

        app(CajaService::class)->configurarMostrador($efectivo->id, $admin->id);
        $guardar('caja-sin-caja-abierta');

        CajaOperativa::create([
            'usuario_operativo_id' => $operativo->id,
            'tipo_caja_efectivo_id' => $efectivo->id,
            'estado' => 'ABIERTA',
            'efectivo_inicial' => 10000,
            'apertura_at' => now(),
        ]);
        $guardar('caja-con-caja-abierta');
    }
}
