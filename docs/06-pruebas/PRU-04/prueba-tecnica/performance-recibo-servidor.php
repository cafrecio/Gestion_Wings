<?php
/**
 * PRU-04 · Prueba técnica · Cuánto tarda y cuánto pesa generar un recibo en el servidor (Claude, 31/10/2026; día 47.9).
 *
 * No deja rastro: el PDF se escribe en una carpeta temporal, no en la de Wings, y todo corre dentro de una transacción
 * que se revierte. Uso: /usr/bin/php82 performance-recibo-servidor.php /home/wingstest/app
 */
$base = $argv[1] ?? '/home/wingstest/app';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wingstest') { fwrite(STDERR, "Base inesperada: solo wingstest.\n"); exit(2); }

use Illuminate\Support\Facades\{DB, Storage};

$tmp = sys_get_temp_dir().'/claude-recibos-'.getmypid();
mkdir($tmp);
config(['filesystems.disks.local.root' => $tmp, 'filesystems.disks.local.throw' => false]);
Storage::forgetDisk('local');
$pagos = DB::table('pagos')->where('estado', '!=', 'ANULADO')->orderByDesc('id')->limit(5)->pluck('id');
$filas = [];
DB::beginTransaction();
try {
    foreach ($pagos as $id) {
        $t = microtime(true); $cpu0 = getrusage();
        $ruta = app(App\Services\ReciboService::class)->generarReciboCuota($id, true);
        $ms = (microtime(true) - $t) * 1000; $cpu1 = getrusage();
        $cpu = (($cpu1['ru_utime.tv_sec'] - $cpu0['ru_utime.tv_sec']) * 1000) + (($cpu1['ru_utime.tv_usec'] - $cpu0['ru_utime.tv_usec']) / 1000);
        $filas[] = ['pago' => $id, 'ms' => round($ms), 'cpu_ms' => round($cpu), 'kb' => round(Storage::size($ruta) / 1024), 'memoria_mb' => round(memory_get_peak_usage(true) / 1048576)];
    }
    $ms = array_column($filas, 'ms'); sort($ms);
    $s = Storage::get($ruta);
    // Qué ocupa lugar dentro del PDF: los flujos más grandes y las fuentes o imágenes que declara.
    preg_match_all('/\/Length (\d+)/', $s, $m);
    rsort($m[1]);
    echo json_encode(['fecha' => date('c'), 'mediana_ms' => $ms[(int) floor(count($ms) / 2)], 'filas' => $filas, 'flujos_mas_grandes_kb' => array_map(fn ($n) => round($n / 1024), array_slice(array_map('intval', $m[1]), 0, 6)),
        'imagenes' => substr_count($s, '/Subtype /Image'), 'fuentes_embebidas' => substr_count($s, '/FontFile'), 'fuentes' => array_values(array_unique(preg_match_all('/\/BaseFont \/([^\s\/]+)/', $s, $f) ? $f[1] : []))], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
} finally {
    DB::rollBack();
    exec('rm -rf '.escapeshellarg($tmp));
}
