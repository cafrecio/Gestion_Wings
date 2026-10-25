<?php
// Controles independientes de lectura, sobre volumen sintetico y la base propia.
$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex') throw new RuntimeException('Base no autorizada.');
use App\Models\Pago;
use App\Services\{PartesPagoCuotaService, ReporteMensualService};
use Illuminate\Support\Facades\DB;

$salida = ['base' => config('database.connections.mysql.database'), 'partes' => []];
$servicio = app(PartesPagoCuotaService::class);
$columnas = ['id', 'mes', 'anio', 'fecha_pago', 'estado', 'detalle_anulacion', 'monto_cuota', 'monto_final'];
$completos = Pago::with('deudasCuota')->orderBy('id')->get();
$recortados = Pago::select($columnas)->with(['deudasCuota' => fn ($q) => $q->select(['deuda_cuotas.id', 'deuda_cuotas.periodo'])])->orderBy('id')->get()->keyBy('id');
$diferencias = [];
foreach ($completos as $pago) {
    if ($servicio->partes($pago) !== $servicio->partes($recortados[$pago->id])) $diferencias[] = $pago->id;
}
$salida['partes'] = ['pagos' => $completos->count(), 'diferentes' => $diferencias];
$anulado = new Pago(['mes' => 10, 'anio' => 2026, 'fecha_pago' => '2026-10-15', 'estado' => Pago::ESTADO_ANULADO,
    'monto_cuota' => '15.84', 'monto_final' => '15.84', 'detalle_anulacion' => ['periodos' => [
        ['deuda_id' => 900001, 'periodo' => '2026-08', 'monto_aplicado' => '10.29'],
        ['deuda_id' => 900002, 'periodo' => '2026-12', 'monto_aplicado' => '5.55'],
    ]]]);
$anulado->id = 900000;
$anulado->setRelation('deudasCuota', collect());
$corto = new Pago();
$corto->setRawAttributes(array_intersect_key($anulado->getAttributes(), array_flip($columnas)));
$corto->setRelation('deudasCuota', collect());
$salida['anulado_sin_pivotes'] = ['iguales' => $servicio->partes($anulado) === $servicio->partes($corto), 'partes' => $servicio->partes($corto)];

DB::beginTransaction();
try {
    $reportes = app(ReporteMensualService::class);
    $mes = today()->format('Y-m');
    $antes = $reportes->enLote(fn () => $reportes->enLote(fn () => $reportes->obtener($mes)));
    try {
        $reportes->enLote(function () use ($reportes, $mes) { $reportes->obtener($mes); throw new RuntimeException('Control de limpieza tras excepcion'); });
    } catch (RuntimeException $e) {
        if ($e->getMessage() !== 'Control de limpieza tras excepcion') throw $e;
    }
    DB::table('cashflow_movimientos')->insert(['fecha' => today()->toDateString(), 'subrubro_id' => DB::table('subrubros')->value('id'),
        'tipo_caja_id' => DB::table('tipos_caja')->value('id'), 'monto' => '12.37', 'usuario_admin_id' => DB::table('users')->where('rol', 'ADMIN')->value('id'),
        'reporte_tipo' => 'INGRESO', 'reporte_clasificacion' => 'APORTE', 'created_at' => now(), 'updated_at' => now()]);
    $despues = $reportes->obtener($mes);
    $salida['lotes_y_excepcion'] = ['aporte_antes_centavos' => $antes['aportes'], 'aporte_despues_centavos' => $despues['aportes'],
        'delta_centavos' => $despues['aportes'] - $antes['aportes'], 've_escritura_nueva' => $despues['aportes'] - $antes['aportes'] === 1237];
} finally { DB::rollBack(); }

$patrones = ['id', 'movId', 'rubroId', 'cajaId', 'alumnoId', 'planId', 'pagoId', 'liquidacionId', 'detalleId'];
$rutas = [];
foreach (app('router')->getRoutes() as $ruta) {
    $parametros = $ruta->parameterNames();
    if (!array_intersect($parametros, $patrones) && !in_array('clave', $parametros)) continue;
    $rutas[] = ['metodos' => $ruta->methods(), 'uri' => $ruta->uri(), 'parametros' => $parametros,
        'patrones' => array_intersect_key($ruta->wheres, array_flip($parametros)), 'accion' => $ruta->getActionName()];
}
$salida['rutas_registradas'] = $rutas;
$salida['api_registrada'] = count(array_filter($rutas, fn ($r) => str_starts_with($r['uri'], 'api/')));
file_put_contents(__DIR__.'/auditoria-adicional.json', json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode(['pagos_comparados' => $completos->count(), 'diferencias' => count($diferencias),
    'anulado_igual' => $salida['anulado_sin_pivotes']['iguales'], 'lote_limpio' => $salida['lotes_y_excepcion']['ve_escritura_nueva'], 'rutas' => count($rutas)]).PHP_EOL;
if ($diferencias || !$salida['anulado_sin_pivotes']['iguales'] || !$salida['lotes_y_excepcion']['ve_escritura_nueva']) exit(1);
