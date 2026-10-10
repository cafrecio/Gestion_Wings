<?php

namespace Tests\Feature;

use App\Models\{Rubro, Subrubro, User};
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/** Dos procesos separados; no usa transacciones de fixtures invisibles al otro pedido. */
class VerificacionT16ConcurrenciaTest extends TestCase
{
    use DatabaseMigrations;

    public function test_dos_pedidos_no_deben_dejar_aporte_en_egreso(): void
    {
        $basePrueba = DB::connection()->getDatabaseName();
        $this->assertContains($basePrueba, ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini']);
        $this->seed(CatalogosSeeder::class);
        $admin = User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
        $rubro = Rubro::create(['nombre' => 'Concurrencia T16', 'tipo' => 'INGRESO']);
        $sub = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Aporte concurrente T16',
            'permitido_para' => 'ADMIN', 'afecta_caja' => false, 'clasificacion_resultado' => 'NEGOCIO']);
        $barrera = storage_path('framework/testing/t16-concurrencia-'.bin2hex(random_bytes(6)));
        mkdir($barrera, 0777, true);
        $script = base_path('docs/06-pruebas/PRU-04/evidencia-t16/pedido-concurrente.php');
        $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $basePrueba,
            'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'];
        $padre = new Process([PHP_BINARY, $script, 'padre', $admin->id, $rubro->id, $sub->id, $barrera], base_path(), $env);
        $hijo = new Process([PHP_BINARY, $script, 'hijo', $admin->id, $rubro->id, $sub->id, $barrera], base_path(), $env);
        foreach ([$padre, $hijo] as $proceso) $proceso->setTimeout(30);
        try {
            $padre->start();
            $hijo->start();
            $hijo->wait();
            $padre->wait();
            foreach ([$padre, $hijo] as $proceso) {
                $this->assertTrue($proceso->isSuccessful(), $proceso->getErrorOutput().$proceso->getOutput());
            }
            file_put_contents($barrera.'/salida-padre.txt', $padre->getOutput());
            file_put_contents($barrera.'/salida-hijo.txt', $hijo->getOutput());
            $this->assertJson(trim($padre->getOutput()), $padre->getOutput());
            $this->assertJson(trim($hijo->getOutput()), $hijo->getOutput());
            $a = json_decode(trim($padre->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            $b = json_decode(trim($hijo->getOutput()), true, flags: JSON_THROW_ON_ERROR);
            foreach ([$a, $b] as $respuesta) {
                $this->assertSame(302, $respuesta['status']);
                $this->assertSame([], $respuesta['validaciones']);
            }
            $final = ['pedidos' => [$a, $b], 'tipo_final' => $rubro->fresh()->tipo,
                'clasificacion_final' => $sub->fresh()->clasificacion_resultado];
            if ($ruta = getenv('T16_V2_EVIDENCIA')) file_put_contents($ruta,
                json_encode(['caso' => 'dos_pedidos_simultaneos'] + $final,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
            $this->assertFalse($final['tipo_final'] === 'EGRESO' && $final['clasificacion_final'] === 'APORTE',
                'Dos pedidos HTTP concurrentes dejaron un APORTE dentro de un rubro de EGRESO.');
        } finally {
            foreach ([$padre, $hijo] as $proceso) if ($proceso->isRunning()) $proceso->stop();
        }
    }
}
