<?php
/**
 * PRU-04 · Prueba técnica · Quince cobros seguidos, cada uno con su recibo (Claude, 31/10/2026; día 47.9).
 *
 * Escribe datos: SOLO en una copia local descartable de la base del sitio, nunca en el servidor. Mide lo que el script de
 * pantallas no mide: el envío del cobro y la primera generación del PDF. Máquina local: sirven las proporciones.
 * Uso, desde la raíz de la aplicación:
 *   APP_ENV=testing DB_DATABASE=<copia> FECHA_SIMULADA="2026-11-25 10:00:00" php performance-cobros-seguidos.php
 */
$raiz = getcwd();
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$base = config('database.connections.mysql.database');
if (!str_starts_with((string) $base, 'wings_testing_')) { fwrite(STDERR, "Solo bases wings_testing_*.\n"); exit(2); }
config(['session.driver' => 'array', 'cache.default' => 'array', 'app.debug' => false, 'mail.default' => 'array', 'queue.default' => 'sync']);
config(['filesystems.disks.local.root' => sys_get_temp_dir().'/wings-perf-'.getmypid()]);

use App\Models\{Alumno, User};
use Illuminate\Support\Facades\DB;

$rocio = User::where('rol', 'OPERATIVO')->where('activo', 1)->where('name', 'like', 'Roc%')->firstOrFail();
$efectivo = app(App\Services\CajaService::class)->tipoCajaMostradorId();
// Un turno abierto: la copia quedó con todas las cajas validadas.
DB::table('cajas_operativas')->insert(['usuario_operativo_id' => $rocio->id, 'estado' => 'ABIERTA', 'apertura_at' => now(), 'tipo_caja_efectivo_id' => $efectivo, 'efectivo_heredado' => 0,
    'efectivo_inicial' => 0, 'efectivo_esperado' => 0, 'usuario_apertura_id' => $rocio->id, 'created_at' => now(), 'updated_at' => now()]);

$consultas = 0;
DB::listen(function () use (&$consultas) { $consultas++; });
$pedir = function (string $metodo, string $url, array $datos = []) use ($kernel, $rocio, &$consultas) {
    app('auth')->forgetGuards();
    app('auth')->guard('web')->setUser($rocio);
    $consultas = 0;
    $t = microtime(true);
    $req = Illuminate\Http\Request::create($url, $metodo, $datos);
    $resp = $kernel->handle($req);
    $ms = (microtime(true) - $t) * 1000;
    $kernel->terminate($req, $resp);
    return [round($ms, 1), $consultas, $resp->getStatusCode(), $resp];
};

// Quince activos al día que todavía no adelantaron diciembre: el mismo cobro de 47.9.
$alumnos = Alumno::where('activo', 1)->where('fecha_alta', '<=', '2026-11-01')->whereDoesntHave('deudaCuotas', fn ($q) => $q->where('periodo', '2026-12')->orWhere('estado', 'PENDIENTE'))->orderBy('id')->limit(15)->get();
$filas = [];
foreach ($alumnos as $a) {
    [$msForm, $cForm] = $pedir('GET', route('web.caja.cobrar', $a->id, false));
    $plan = app(App\Services\PagoCuotaService::class)->obtenerPlanParaPeriodo($a->id, '2026-12')?->plan;
    $importe = (float) ($plan?->precio_mensual ?? 30000);
    [$msPost, $cPost, $estado] = $pedir('POST', route('web.caja.pagar', $a->id, false), ['tipo_caja_id' => $efectivo, 'periodos' => ['2026-12'], 'montos_cuota' => ['2026-12' => $importe], 'confirmar_pago_adelantado' => 1]);
    $pago = DB::table('pagos')->where('alumno_id', $a->id)->orderByDesc('id')->first();
    [$msPdf, $cPdf, $estadoPdf, $respPdf] = $pedir('GET', route('web.recibos.cuota', $pago->id, false));
    [$msPdf2] = $pedir('GET', route('web.recibos.cuota', $pago->id, false));
    $filas[] = ['alumno' => $a->id, 'form_ms' => $msForm, 'form_consultas' => $cForm, 'cobro_ms' => $msPost, 'cobro_consultas' => $cPost, 'cobro_estado' => $estado,
        'recibo_primera_ms' => $msPdf, 'recibo_estado' => $estadoPdf, 'recibo_kb' => round(strlen((string) $respPdf->getContent()) / 1024, 1), 'recibo_segunda_ms' => $msPdf2];
}
$mediana = function (array $v) { sort($v); return $v[(int) floor(count($v) / 2)] ?? null; };
$col = fn (string $k) => array_column($filas, $k);
echo json_encode(['fecha_wings' => now()->toDateTimeString(), 'base' => $base, 'maquina' => 'local (XAMPP), no el servidor', 'cobros' => count($filas),
    'cobros_registrados' => count(array_filter($filas, fn ($f) => $f['cobro_estado'] === 302)),
    'mediana' => ['formulario_ms' => $mediana($col('form_ms')), 'cobro_ms' => $mediana($col('cobro_ms')), 'recibo_primera_ms' => $mediana($col('recibo_primera_ms')), 'recibo_segunda_ms' => $mediana($col('recibo_segunda_ms'))],
    'maximo' => ['formulario_ms' => max($col('form_ms')), 'cobro_ms' => max($col('cobro_ms')), 'recibo_primera_ms' => max($col('recibo_primera_ms'))],
    'primero_y_ultimo_cobro_ms' => [$filas[0]['cobro_ms'] ?? null, end($filas)['cobro_ms'] ?? null],
    'consultas_por_cobro' => $mediana($col('cobro_consultas')), 'filas' => $filas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
