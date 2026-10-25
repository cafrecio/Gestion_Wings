<?php
// T79: mismo resultado antes y después. Agrega casos dentro de una transacción que se revierte y
// vuelca obtener() de cada mes, evolucion() y los saldos. Solo wings_testing_claude.
// Uso: php comparar-t79.php <salida.json>
$base = 'C:/xampp/htdocs/gestion-wings';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') exit(2);
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $ahora = '2026-10-25 10:00:00';
    DB::table('reporte_cobertura')->where('id', 1)->update(['desde' => '2024-10-01']);
    foreach ([[1, '2024-10-01', 150000], [2, '2024-10-01', 2500029], [3, '2025-06-15', -700], [1, '2026-10-20', 1234]] as [$caja, $f, $c]) {
        DB::table('reporte_eventos')->insert(['tipo' => 'CAJA_INICIAL', 'origen_id' => $caja, 'fecha' => $f, 'delta_centavos' => $c, 'motivo' => 'PRUEBA_T79', 'registrado_en' => $ahora]);
    }
    foreach ([['2025-03-10', 1, 1, '10.29'], ['2025-03-11', 2, 2, '-0.07'], ['2026-10-24', 3, 1, '1999.99'], ['2026-10-25', 3, 2, '-5.55'], ['2026-11-02', 1, 1, '77.77']] as [$f, $tc, $sub, $m]) {
        DB::table('cashflow_movimientos')->insert(['fecha' => $f, 'subrubro_id' => $sub, 'tipo_caja_id' => $tc, 'monto' => $m, 'usuario_admin_id' => 1,
            'reporte_tipo' => $sub === 1 ? 'INGRESO' : 'EGRESO', 'reporte_clasificacion' => 'NEGOCIO', 'created_at' => $ahora, 'updated_at' => $ahora]);
    }
    $cerrada = DB::table('cajas_operativas')->insertGetId(['usuario_operativo_id' => 2, 'apertura_at' => '2026-10-23 08:00:00', 'estado' => 'CERRADA', 'created_at' => $ahora, 'updated_at' => $ahora]);
    $abierta = DB::table('cajas_operativas')->where('estado', 'ABIERTA')->value('id');
    $movs = [
        [$cerrada, '2026-10-23', 1, 1, '1500.10', null, 'ACTIVO'],
        [$cerrada, '2026-10-23', 1, 2, '300.00', 'EGRESO', 'ACTIVO'],
        [$cerrada, '2026-10-23', 2, 2, '-45.50', null, 'ACTIVO'],
        [$cerrada, '2026-10-23', 1, 1, '999.00', null, 'CANCELADO'],
        [$abierta, '2026-10-25', 3, 1, '20.01', 'INGRESO', 'ACTIVO'],
        [$abierta, '2026-10-25', 3, 1, '-12.00', null, 'ACTIVO'],
        [$abierta, '2026-10-25', 1, 2, '0.29', null, 'ACTIVO'],
        [$abierta, '2026-10-27', 1, 1, '50.00', null, 'ACTIVO'],
        [$abierta, '2025-09-30', 2, 1, '33.33', null, 'ACTIVO'],
    ];
    foreach ($movs as [$caja, $f, $tc, $sub, $m, $tipo, $estado]) {
        DB::table('movimientos_operativos')->insert(['caja_operativa_id' => $caja, 'fecha' => $f, 'tipo_caja_id' => $tc, 'subrubro_id' => $sub, 'monto' => $m,
            'usuario_id' => 2, 'reporte_tipo' => $tipo, 'estado' => $estado, 'created_at' => $ahora, 'updated_at' => $ahora]);
    }

    $s = app(App\Services\ReporteMensualService::class);
    $salida = ['meses' => [], 'saldos' => []];
    for ($p = Carbon\CarbonImmutable::parse('2024-09-01'); $p->format('Y-m') <= today()->format('Y-m'); $p = $p->addMonth()) {
        $mes = $p->format('Y-m');
        $salida['meses'][$mes] = $s->obtener($mes);
        $salida['meses'][$mes.'·deporte1'] = $s->obtener($mes, 1);
        $fin = min($p->endOfMonth()->toDateString(), today()->toDateString());
        $salida['saldos'][$fin] = method_exists($s, 'saldosPorCaja') ? $s->saldosPorCaja($fin) : $salida['meses'][$mes]['cajas'];
    }
    $salida['evolucion'] = $s->evolucion(today()->format('Y-m'));
    $salida['evolucion_mes_cerrado'] = $s->evolucion('2026-06', 1);
    file_put_contents($argv[1], json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo 'meses: '.count($salida['meses']).', cajas hoy: '.json_encode($salida['saldos'][today()->toDateString()])."\n";
} finally {
    DB::rollBack();
}
