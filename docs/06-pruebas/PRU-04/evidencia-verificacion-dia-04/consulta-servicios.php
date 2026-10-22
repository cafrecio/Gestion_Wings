<?php
// Ejecutar por stdin con PHP 8.2 desde /home/wingstest/app. No modifica el reloj.
require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('staging') || Illuminate\Support\Facades\DB::selectOne('SELECT DATABASE() AS db')->db !== 'wingstest') {
    throw new RuntimeException('Solo staging/wingstest; consulta rechazada.');
}
Illuminate\Support\Facades\DB::statement('SET SESSION TRANSACTION READ ONLY');
$salida = ['entorno' => $app->environment(), 'base' => 'wingstest',
    'fecha_aplicacion' => now()->format('Y-m-d H:i:sP'),
    'sesion_hasta_cerrar' => config('session.expire_on_close')];
$cobranza = app(App\Services\CobranzaEstadoService::class);
$salida['contadores'] = array_count_values($cobranza->estadosParaAlumnos(App\Models\Alumno::where('activo', true)->get()));
foreach ([241, 245, 246, 270, 331, 333, 341] as $id) {
    $estado = $cobranza->estadoAlumno($id);
    $salida['estados'][$id] = ['estado' => $estado['estado'], 'dias_gracia_restantes' => $estado['dias_gracia_restantes']];
}
$salida['limite_mora'] = $cobranza->limiteDeAlta(App\Models\Alumno::findOrFail(341))->format('Y-m-d H:i:sP');
Illuminate\Support\Facades\DB::enableQueryLog();
Illuminate\Support\Facades\DB::flushQueryLog();
$inicio = hrtime(true);
$reporte = app(App\Services\ReporteMensualService::class)->obtener('2026-10');
$duracion = (hrtime(true) - $inicio) / 1000000;
$consultas = Illuminate\Support\Facades\DB::getQueryLog();
foreach (['ingresos', 'egresos', 'resultado', 'resultado_global', 'aportes', 'retiros', 'disponible', 'sin_clasificar'] as $clave) {
    $salida['reporte_octubre_centavos'][$clave] = $reporte[$clave];
}
$salida['reporte_octubre_centavos']['deuda'] = $reporte['deuda']['total'];
$salida['avisos'] = $reporte['avisos'];
$salida['observacion_rendimiento_cli'] = ['consulta' => 'ReporteMensualService::obtener(2026-10)',
    'llamadas_sql' => count($consultas), 'sql_ms' => round(array_sum(array_column($consultas, 'time')), 2),
    'duracion_ms' => round($duracion, 2), 'alumnos_activos' => array_sum($salida['contadores']),
    'limite' => 'Una lectura CLI con estos datos; no mide navegador, carga concurrente ni crecimiento.'];
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
