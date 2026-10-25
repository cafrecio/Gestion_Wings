<?php
/**
 * PRU-04 · Prueba técnica · Performance de pantallas (Claude, 25/10/2026).
 *
 * Abre por dentro (sin navegador) cada pantalla que usaron los días 1 a 6, con el usuario de cada
 * rol, y mide consultas a la base y tiempo. SOLO LECTURA: sesión y caché en memoria, y todo dentro
 * de una transacción que se revierte al final. Tres corridas por pantalla; se informa la mediana.
 * Uso: php performance-pantallas.php <ruta-app> <base-esperada>
 */
$base = $argv[1] ?? '/home/wingstest/app';
$esperada = $argv[2] ?? 'wingstest';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== $esperada) { fwrite(STDERR, "Base inesperada.\n"); exit(2); }
config(['session.driver' => 'array', 'cache.default' => 'array', 'app.debug' => false]);

use App\Models\{User, Alumno, Clase, CajaOperativa};
use Illuminate\Support\Facades\DB;

$admin = User::where('rol', 'ADMIN')->where('activo', 1)->orderBy('id')->first();
$oper = User::where('rol', 'OPERATIVO')->where('activo', 1)->orderBy('id')->first();
$prof = User::where('rol', 'PROFESOR')->where('activo', 1)->orderBy('id')->first();
$alumno = Alumno::where('activo', 1)->whereHas('deudaCuotas')->orderByDesc('id')->first() ?? Alumno::first();
$clase = Clase::whereHas('asistencias')->orderByDesc('fecha')->first() ?? Clase::first();
$caja = CajaOperativa::orderByDesc('id')->first();
$mes = now()->format('Y-m');

$r = fn ($n, $p = []) => app('router')->has($n) ? route($n, $p, false) : null;
$pantallas = [
    ['ADMIN', 'Inicio del dueño', $r('admin.dashboard')],
    ['ADMIN', 'Cobranza', $r('web.cobranza.index')],
    ['ADMIN', 'Alumnos', $r('web.alumnos.index')],
    ['ADMIN', 'Ficha de alumno', $r('web.alumnos.show', $alumno?->id)],
    ['ADMIN', 'Cajas', $r('web.caja.index')],
    ['ADMIN', 'Detalle de caja', $caja ? $r('web.caja.detalle', $caja->id) : null],
    ['ADMIN', 'Cashflow', $r('web.cashflow.index')],
    ['ADMIN', 'Reportes · Finanzas', $r('web.reportes.index', ['mes' => $mes])],
    ['ADMIN', 'Reportes · Sueldos', $r('web.reportes.sueldos', ['mes' => $mes])],
    ['ADMIN', 'Reportes · Alumnos', $r('web.reportes.alumnos', ['mes' => $mes])],
    ['ADMIN', 'Clases', $r('web.clases.index')],
    ['ADMIN', 'Ficha de clase', $r('web.clases.show', $clase?->id)],
    ['ADMIN', 'Liquidaciones', $r('web.liquidaciones.index')],
    ['ADMIN', 'Movimientos', $r('web.movimientos.index')],
    ['OPERATIVO', 'Inicio de la operativa', $r('web.operativo.dashboard')],
    ['OPERATIVO', 'Cobranza', $r('web.cobranza.index')],
    ['OPERATIVO', 'Buscador de Cobrar', $r('web.caja.cobrar-cuota')],
    ['OPERATIVO', 'Alumnos', $r('web.alumnos.index')],
    ['OPERATIVO', 'Clases', $r('web.clases.index')],
    ['PROFESOR', 'Clases', $r('web.clases.index')],
    ['PROFESOR', 'Ficha de clase', $r('web.clases.show', $clase?->id)],
];
$usuarios = ['ADMIN' => $admin, 'OPERATIVO' => $oper, 'PROFESOR' => $prof];

$consultas = 0; $msSql = 0.0;
DB::listen(function ($q) use (&$consultas, &$msSql) { $consultas++; $msSql += $q->time; });

$res = [];
DB::beginTransaction();
try {
    foreach ($pantallas as [$rol, $nombre, $url]) {
        if (!$url || !$usuarios[$rol]) { $res[] = compact('rol', 'nombre') + ['url' => $url, 'error' => 'ruta o usuario inexistente']; continue; }
        $corridas = [];
        for ($i = 0; $i < 3; $i++) {
            app('auth')->forgetGuards();
            app('auth')->guard('web')->setUser($usuarios[$rol]);
            $consultas = 0; $msSql = 0.0;
            $t = microtime(true);
            $req = Illuminate\Http\Request::create($url, 'GET');
            $resp = $kernel->handle($req);
            $ms = (microtime(true) - $t) * 1000;
            $corridas[] = ['ms' => round($ms, 1), 'consultas' => $consultas, 'ms_sql' => round($msSql, 1), 'estado' => $resp->getStatusCode(),
                'kb' => round(strlen((string) $resp->getContent()) / 1024, 1), 'redirige' => $resp->isRedirection() ? $resp->headers->get('Location') : null];
            $kernel->terminate($req, $resp);
        }
        usort($corridas, fn ($a, $b) => $a['ms'] <=> $b['ms']);
        $res[] = ['rol' => $rol, 'pantalla' => $nombre, 'url' => $url] + $corridas[1];
    }
} finally {
    DB::rollBack();
}
echo json_encode(['fecha_wings' => now()->toDateTimeString(), 'base' => $esperada,
    'volumen' => ['alumnos_activos' => Alumno::where('activo', 1)->count(), 'cuotas' => DB::table('deuda_cuotas')->count(),
        'pagos' => DB::table('pagos')->count(), 'clases' => DB::table('clases')->count(), 'asistencias' => DB::table('asistencias')->count(),
        'cashflow' => DB::table('cashflow_movimientos')->count()],
    'pantallas' => $res], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
