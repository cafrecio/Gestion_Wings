<?php

namespace Tests\Feature;

use App\Models\{Deporte, User};
use App\Services\{ReporteMensualService, ReporteMensualServiceAntesT115};
use Carbon\Carbon;
use Database\Seeders\ReportesEscenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

require_once __DIR__.'/ReporteMensualServiceAntesT115.php';

/** Compara el servicio anterior a 30ea9a41 y main sin modificar producto ni datos entre lecturas. */
class ComparacionReportesT115CodexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'wings_testing_codex') throw new \RuntimeException('Usar exclusivamente wings_testing_codex.');
        parent::setUp();
        DB::table('primera_carga')->where('id', 1)->update(['estado' => 'PENDIENTE']);
        $this->seed(ReportesEscenarioSeeder::class);
        Carbon::setTestNow('2026-10-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // Compara todos los campos e integrantes, ignorando exclusivamente el orden de listas.
    private function canonico(mixed $valor): mixed
    {
        if (!is_array($valor)) return $valor;
        $lista = array_is_list($valor);
        $valor = array_map(fn ($v) => $this->canonico($v), $valor);
        if ($lista) usort($valor, fn ($a, $b) => strcmp(json_encode($a), json_encode($b)));
        else ksort($valor);
        return $valor;
    }

    public function test_importes_y_filas_sin_cambios_en_siete_meses_todos_los_deportes_y_pedido_web(): void
    {
        $antes = new ReporteMensualServiceAntesT115();
        $despues = new ReporteMensualService();
        $resumen = [];
        $diferenciasDeOrden = 0;
        DB::flushQueryLog();
        DB::enableQueryLog();
        foreach (range(4, 10) as $mesNumero) {
            $mes = sprintf('2026-%02d', $mesNumero);
            foreach ([null, ...Deporte::orderBy('id')->pluck('id')->all()] as $deporte) {
                $a = $antes->enLote(fn () => [$antes->obtener($mes, $deporte), $antes->evolucion($mes, $deporte)]);
                $b = $despues->enLote(fn () => [$despues->obtener($mes, $deporte), $despues->evolucion($mes, $deporte)]);
                $this->assertSame($this->canonico($a), $this->canonico($b), "$mes / deporte ".($deporte ?? 'todos'));
                if ($a !== $b) $diferenciasDeOrden++;
                $resumen[] = ['mes' => $mes, 'deporte' => $deporte, 'ingresos_centavos' => $b[0]['ingresos'],
                    'egresos_centavos' => $b[0]['egresos'], 'resultado_centavos' => $b[0]['resultado'],
                    'deuda_centavos' => $b[0]['deuda']['total'], 'por_pagar_centavos' => $b[0]['por_pagar']['total'],
                    'disponible_centavos' => $b[0]['disponible'], 'todos_los_campos_y_filas_iguales' => true];
            }
        }
        $consultas = DB::getQueryLog();
        DB::disableQueryLog();
        foreach ($consultas as $consulta) {
            $this->assertMatchesRegularExpression('/^\s*(select|show)\b/i', $consulta['query'], 'La comparación no debe escribir.');
        }

        $this->actingAs(User::where('rol', 'ADMIN')->firstOrFail());
        $this->app->instance(ReporteMensualService::class, $antes);
        $webAntes = $this->get('/reportes?mes=2026-10')->assertOk()->assertViewIs('reportes.index');
        $this->app->instance(ReporteMensualService::class, $despues);
        $webDespues = $this->get('/reportes?mes=2026-10')->assertOk()->assertViewIs('reportes.index');
        foreach (['reporte', 'evolucion'] as $campo) {
            $this->assertSame($this->canonico($webAntes->viewData($campo)), $this->canonico($webDespues->viewData($campo)));
        }
        file_put_contents(__DIR__.'/comparacion-reportes.json', json_encode([
            'base' => 'wings_testing_codex', 'reloj' => now()->toIso8601String(),
            'antes' => '30ea9a41^:app/Services/ReporteMensualService.php (solo nombre de clase adaptado)',
            'despues' => 'ReporteMensualService actual, identico a 30ea9a41',
            'sha256_servicio_actual' => hash_file('sha256', app_path('Services/ReporteMensualService.php')),
            'sha256_copia_historica' => hash_file('sha256', __DIR__.'/ReporteMensualServiceAntesT115.php'),
            'comparaciones' => $resumen,
            'comparaciones_con_diferencias_solo_de_orden' => $diferenciasDeOrden,
            'consultas_de_comparacion_solo_lectura' => count($consultas),
            'pedidos_web_antes_y_despues' => ['estado' => 200, 'reporte_y_evolucion_iguales' => true],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
    }
}
