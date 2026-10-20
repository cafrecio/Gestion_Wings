<?php

if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex_t61') {
    throw new RuntimeException('Solo lectura de la base ficticia local T61.');
}
$root = dirname(__DIR__, 4);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cabeceras = Illuminate\Support\Facades\DB::table('cashflow_transferencias')
    ->select('id', 'salida_id', 'destino_id', 'monto', 'fecha')->orderBy('id')->limit(5)->get();
$movimientos = App\Models\CashflowMovimiento::where('referencia_tipo', App\Models\CashflowMovimiento::REF_TRANSFERENCIA)
    ->select('id', 'referencia_tipo', 'referencia_id', 'tipo_caja_id', 'subrubro_id', 'fecha', 'monto', 'reporte_clasificacion')
    ->orderBy('id')->limit(10)->get();
$reporte = app(App\Services\ReporteMensualService::class)->obtener('2026-10');
echo json_encode(['base' => config('database.connections.mysql.database'),
    'transferencias' => $cabeceras, 'movimientos' => $movimientos,
    'reporte' => Illuminate\Support\Arr::only($reporte, ['ingresos', 'egresos', 'resultado', 'resultado_global', 'disponible', 'cajas']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
