<?php

// Segundo proceso de las pruebas de bloqueo. Nunca admite la base del club.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = getenv('DB_DATABASE');
if (!in_array($database, ['wings_testing', 'wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) exit(2);
config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database]);
Illuminate\Support\Facades\DB::purge('mysql');
Carbon\Carbon::setTestNow('2026-10-13 12:00:00');
$datos = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
echo 'READY:'.Illuminate\Support\Facades\DB::selectOne('SELECT CONNECTION_ID() AS id')->id."\n";
flush();
Illuminate\Support\Facades\DB::connection()->beforeExecuting(function ($sql) {
    echo 'SQL:'.$sql."\n";
    flush();
});
try {
    $cuotas = new App\Services\PagoCuotaService(app(App\Services\CajaService::class), null);
    if ($datos['accion'] === 'condonar') {
        if ($datos['inscripcion']) app(App\Services\InscripcionService::class)->condonar($datos['cargo'], 'Perdón concurrente autorizado', $datos['admin']);
        else $cuotas->condonarDeuda($datos['cuota'], 'Perdón concurrente autorizado', $datos['admin']);
    } else {
        $cuotas->registrarPagoCuotaAdmin(['alumno_id' => $datos['alumno'], 'usuario_admin_id' => $datos['admin'],
            'tipo_caja_id' => $datos['tipo_caja'], 'fecha_pago' => '2026-10-13', 'monto_entregado' => $datos['importe'],
            'items' => $datos['inscripcion'] ? [] : [['periodo' => '2026-10', 'monto' => $datos['importe']]]]);
    }
    echo "RESULT:OK\n";
} catch (Throwable $e) {
    echo 'RESULT:ERROR:'.$e->getMessage()."\n";
}
