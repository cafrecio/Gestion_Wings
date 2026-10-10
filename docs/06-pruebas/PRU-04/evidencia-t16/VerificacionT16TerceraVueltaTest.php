<?php

namespace Tests\Feature;

use App\Models\{Rubro, Subrubro, User};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Ocho cruces propios: dos procesos, espera InnoDB comprobada y liberación coordinada. */
class VerificacionT16TerceraVueltaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertContains(DB::connection()->getDatabaseName(),
            ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini']);
        // Fixtures visibles a los dos procesos; sin transacción exterior ni rollback de migraciones.
        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    public function test_aporte_edicion_tipo_primero(): void { $this->cruce('APORTE', 'update', 'rubro'); }
    public function test_aporte_edicion_clasificacion_primero(): void { $this->cruce('APORTE', 'update', 'subrubro'); }
    public function test_retiro_edicion_tipo_primero(): void { $this->cruce('RETIRO', 'update', 'rubro'); }
    public function test_retiro_edicion_clasificacion_primero(): void { $this->cruce('RETIRO', 'update', 'subrubro'); }
    public function test_aporte_alta_tipo_primero(): void { $this->cruce('APORTE', 'store', 'rubro'); }
    public function test_aporte_alta_clasificacion_primero(): void { $this->cruce('APORTE', 'store', 'subrubro'); }
    public function test_retiro_alta_tipo_primero(): void { $this->cruce('RETIRO', 'store', 'rubro'); }
    public function test_retiro_alta_clasificacion_primero(): void { $this->cruce('RETIRO', 'store', 'subrubro'); }

    private function esperar(callable $condicion, string $motivo): mixed
    {
        $limite = microtime(true) + 15;
        do {
            if ($valor = $condicion()) return $valor;
            usleep(250000);
        } while (microtime(true) < $limite);
        $this->fail($motivo);
    }

    private function observarEspera(int $segundo, int $primero, string $barrera): ?array
    {
        // Asociar las dos conexiones con sus transacciones y comprobar la espera entre ellas.
        $transacciones = DB::select('SELECT trx_id, trx_mysql_thread_id, trx_query
            FROM information_schema.INNODB_TRX WHERE trx_mysql_thread_id IN (?, ?)', [$segundo, $primero]);
        $porConexion = [];
        foreach ($transacciones as $tx) $porConexion[(int) $tx->trx_mysql_thread_id] = $tx;
        $esperas = isset($porConexion[$segundo], $porConexion[$primero]) ? DB::select(
            'SELECT requesting_trx_id, blocking_trx_id FROM information_schema.INNODB_LOCK_WAITS
             WHERE requesting_trx_id = ? AND blocking_trx_id = ?',
            [$porConexion[$segundo]->trx_id, $porConexion[$primero]->trx_id]) : [];
        file_put_contents($barrera.'/observacion-candado.json', json_encode(
            ['transacciones' => $transacciones, 'esperas' => $esperas], JSON_THROW_ON_ERROR));
        return $esperas ? ['esperando' => $segundo, 'reteniendo' => $primero,
            'consulta' => $porConexion[$segundo]->trx_query, 'transacciones' => $transacciones,
            'esperas' => $esperas] : null;
    }

    private function cruce(string $clase, string $accion, string $primeraOperacion): void
    {
        $basePrueba = DB::connection()->getDatabaseName();
        $this->assertContains($basePrueba, ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini']);
        $this->seed(CatalogosSeeder::class);
        $admins = [User::factory()->create(['rol' => 'ADMIN', 'activo' => true]),
            User::factory()->create(['rol' => 'ADMIN', 'activo' => true])];
        $tipoInicial = $clase === 'APORTE' ? 'INGRESO' : 'EGRESO';
        $tipoNuevo = $clase === 'APORTE' ? 'EGRESO' : 'INGRESO';
        $rubro = Rubro::create(['nombre' => 'Rubro cruce T16', 'tipo' => $tipoInicial]);
        $nombreSub = 'Subrubro cruce T16';
        $sub = $accion === 'update' ? Subrubro::create(['rubro_id' => $rubro->id,
            'nombre' => $nombreSub, 'permitido_para' => 'ADMIN', 'afecta_caja' => false,
            'clasificacion_resultado' => 'NEGOCIO']) : null;
        $pedidos = [
            'rubro' => ['operacion' => 'rubro', 'metodo' => 'PUT', 'ruta' => '/rubros/'.$rubro->id,
                'datos' => ['nombre' => $rubro->nombre, 'tipo' => $tipoNuevo]],
            'subrubro' => ['operacion' => 'subrubro', 'metodo' => $sub ? 'PUT' : 'POST',
                'ruta' => '/rubros/'.$rubro->id.'/subrubros'.($sub ? '/'.$sub->id : ''),
                'datos' => ['nombre' => $nombreSub, 'permitido_para' => 'ADMIN',
                    'afecta_caja' => false, 'clasificacion_resultado' => $clase]],
        ];
        $segundaOperacion = $primeraOperacion === 'rubro' ? 'subrubro' : 'rubro';
        $config = ['primero' => $pedidos[$primeraOperacion] + ['admin_id' => $admins[0]->id],
            'segundo' => $pedidos[$segundaOperacion] + ['admin_id' => $admins[1]->id]];
        $barrera = storage_path('framework/testing/t16-v3-'.bin2hex(random_bytes(6)));
        mkdir($barrera, 0777, true);
        $configPath = $barrera.'/pedidos.json';
        file_put_contents($configPath, json_encode($config, JSON_THROW_ON_ERROR));
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $basePrueba,
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'];
        $script = base_path('docs/06-pruebas/PRU-04/evidencia-t16/pedido-v3.php');
        $a = new Process([PHP_BINARY, $script, $configPath, 'primero'], base_path(), $env);
        $b = new Process([PHP_BINARY, $script, $configPath, 'segundo'], base_path(), $env);
        foreach ([$a, $b] as $proceso) $proceso->setTimeout(35);
        try {
            $a->start();
            $this->esperar(fn () => is_file($barrera.'/primero-con-candado.json'),
                'El primer pedido no tomó el candado dentro del plazo.');
            $candado = json_decode(file_get_contents($barrera.'/primero-con-candado.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $candado['transaccion']);
            $this->assertContains($rubro->id, $candado['parametros']);
            $this->assertTrue($a->isRunning());
            $b->start();
            $this->esperar(fn () => is_file($barrera.'/segundo-iniciado.json'), 'El segundo pedido no comenzó.');
            $segundo = json_decode(file_get_contents($barrera.'/segundo-iniciado.json'), true, flags: JSON_THROW_ON_ERROR);
            $espera = $this->esperar(fn () => $this->observarEspera(
                $segundo['conexion'], $candado['conexion'], $barrera),
                'No se observó al segundo pedido esperando el candado del primero.');
            $this->assertNotSame($segundo['conexion'], $candado['conexion']);
            $this->assertTrue($b->isRunning());
            file_put_contents($barrera.'/liberar-primero', 'liberar');
            $a->wait();
            $b->wait();
            $respuestas = [];
            foreach ([$a, $b] as $proceso) {
                file_put_contents($barrera.'/salida-'.count($respuestas).'.txt', $proceso->getOutput().$proceso->getErrorOutput());
                $this->assertTrue($proceso->isSuccessful(), $proceso->getOutput().$proceso->getErrorOutput());
                $this->assertJson(trim($proceso->getOutput()), $proceso->getOutput());
                $respuestas[] = json_decode(trim($proceso->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            }
            [$primero, $ultimo] = $respuestas;
            foreach ($respuestas as $respuesta) {
                $this->assertSame(302, $respuesta['status']);
                $this->assertSame(0, $respuesta['transaccion_al_terminar']);
                $this->assertLessThan(15, $respuesta['segundos']);
            }
            $this->assertNotEmpty($primero['success']);
            $this->assertNull($primero['error']);
            $this->assertSame([], $primero['validaciones']);
            if ($primeraOperacion === 'rubro') {
                $this->assertSame(['clasificacion_resultado' => ['Esa opción no corresponde a este rubro.']], $ultimo['validaciones']);
                $this->assertSame($tipoNuevo, $rubro->fresh()->tipo);
                if ($sub) $this->assertSame('NEGOCIO', $sub->fresh()->clasificacion_resultado);
                else $this->assertDatabaseMissing('subrubros', ['nombre' => $nombreSub]);
            } else {
                $this->assertStringContainsString('Cambiá primero qué es ese subrubro.', $ultimo['error']);
                $this->assertSame([], $ultimo['validaciones']);
                $this->assertSame($tipoInicial, $rubro->fresh()->tipo);
                $this->assertSame($clase, Subrubro::where('nombre', $nombreSub)->sole()->clasificacion_resultado);
            }
            $this->assertEmpty($ultimo['success']);
            $filas = DB::table('subrubros')->where('rubro_id', $rubro->id)->get(['nombre', 'clasificacion_resultado'])->all();
            $registro = ['caso' => $clase.'-'.$accion.'-'.$primeraOperacion.'-primero',
                'candado_primero' => $candado, 'espera_verificada' => (array) $espera,
                'pedidos' => $respuestas, 'tipo_final' => $rubro->fresh()->tipo, 'subrubros_finales' => $filas];
            if ($ruta = getenv('T16_V3_EVIDENCIA')) file_put_contents($ruta,
                json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, FILE_APPEND);
            // Un pedido posterior confirma que también el rechazo soltó el candado.
            $this->actingAs($admins[0])->put('/rubros/'.$rubro->id,
                ['nombre' => $rubro->nombre, 'tipo' => $rubro->fresh()->tipo])->assertSessionHas('success');
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            file_put_contents($barrera.'/liberar-primero', 'liberar');
            foreach ([$a, $b] as $numero => $proceso) {
                if ($proceso->isRunning()) $proceso->stop();
                file_put_contents($barrera.'/diagnostico-'.$numero.'.txt',
                    $proceso->getOutput().$proceso->getErrorOutput());
            }
        }
    }
}
