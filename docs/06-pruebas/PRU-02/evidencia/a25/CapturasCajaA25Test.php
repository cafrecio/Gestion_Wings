<?php

namespace Tests\Feature;

use App\Models\MovimientoOperativo;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Produce respuestas reales de Wings para capturarlas; no fabrica maquetas. */
class CapturasCajaA25Test extends TestCase
{
    use RefreshDatabase;

    public function test_genera_respuestas_reales_de_las_cinco_pantallas_y_login(): void
    {
        $this->assertSame('wings_testing_codex', config('database.connections.mysql.database'));
        $this->assertSame(realpath(dirname(__DIR__, 5)), realpath(base_path()));
        $this->assertSame(realpath(app_path('Services/CajaService.php')), (new \ReflectionClass(CajaService::class))->getFileName());
        $admin = User::factory()->create(['name' => 'Administración · prueba A25', 'rol' => User::ROL_ADMIN, 'activo' => true]);
        $operativo = User::factory()->create(['name' => 'Mostrador · prueba A25', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
        $tipo = TipoCaja::create(['nombre' => 'Efectivo', 'abreviatura' => 'EFE', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Ingresos de prueba', 'tipo' => 'INGRESO']);
        $subrubro = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Ingreso de prueba', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true]);
        $this->guardar('login', $this->get('/login')->assertOk()->getContent());
        $this->guardar('configuracion', $this->actingAs($admin)->get(route('web.caja.configuracion'))->assertOk()->getContent());
        $service = app(CajaService::class);
        $service->configurarMostrador($tipo->id, $admin->id);
        $this->guardar('indice', $this->actingAs($operativo)->get(route('web.caja.index'))->assertOk()->getContent());
        $this->guardar('apertura', $this->get(route('web.caja.apertura'))->assertOk()->getContent());
        $caja = $service->abrirCajaOperativa($operativo->id, ['efectivo_inicial' => 10000, 'confirmacion' => true]);
        MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'tipo_caja_id' => $tipo->id, 'subrubro_id' => $subrubro->id, 'monto' => 25000, 'fecha' => today(), 'usuario_id' => $operativo->id, 'estado' => 'ACTIVO']);
        $this->guardar('cierre', $this->withSession(['_old_input' => ['efectivo_contado' => 34000, 'cambio_retenido' => 10000]])
            ->get(route('web.caja.cierre', $caja->id))->assertOk()->getContent());
        $service->cerrarCajaOperativa($caja->id, $operativo->id, false, ['efectivo_contado' => 34000, 'cambio_retenido' => 10000]);
        $this->guardar('resumen', $this->actingAs($admin)->get(route('web.caja.resumen', $caja->id))->assertOk()->getContent());
    }

    private function guardar(string $nombre, string $html): void
    {
        $destino = public_path('a25-evidencia');
        if (!is_dir($destino)) mkdir($destino, 0755, true);
        file_put_contents($destino.'/'.$nombre.'.html', $html);
        // Solo marco de medición. La pantalla interior sigue siendo la respuesta de Laravel.
        file_put_contents($destino.'/'.$nombre.'-375.html', '<!doctype html><meta charset="utf-8"><title>A25 · 375</title><iframe title="Pantalla real de Wings a 375" src="'.$nombre.'.html" width="375" height="1000" style="border:0"></iframe>');
    }
}
