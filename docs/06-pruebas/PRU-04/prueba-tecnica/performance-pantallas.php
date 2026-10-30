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
    // Día 23: lo que abrió el dueño al liquidar octubre.
    ['ADMIN', 'Nueva liquidación', $r('web.liquidaciones.create')],
    ['ADMIN', 'Liquidación por hora', ($l = DB::table('liquidaciones')->where('tipo', 'HORA')->orderByDesc('total_calculado')->value('id')) ? $r('web.liquidaciones.show', $l) : null],
    ['ADMIN', 'Liquidación por comisión', ($l = DB::table('liquidaciones')->where('tipo', 'COMISION')->orderByDesc('total_calculado')->value('id')) ? $r('web.liquidaciones.show', $l) : null],
    ['ADMIN', 'Reportes · Finanzas del mes anterior', $r('web.reportes.index', ['mes' => now()->subMonthNoOverflow()->format('Y-m')])],
    ['ADMIN', 'Reportes · Sueldos del mes anterior', $r('web.reportes.sueldos', ['mes' => now()->subMonthNoOverflow()->format('Y-m')])],
    // Días 24 a 28: Nueva liquidación con otro mes, ficha de un inactivo, Cashflow con los sueldos y la caja abierta.
    ['ADMIN', 'Nueva liquidación · mes anterior', $r('web.liquidaciones.create', ['mes' => now()->subMonthNoOverflow()->month, 'anio' => now()->subMonthNoOverflow()->year])],
    ['ADMIN', 'Ficha de un inactivo', ($i = Alumno::where('activo', 0)->orderByDesc('id')->value('id')) ? $r('web.alumnos.show', $i) : null],
    ['ADMIN', 'Nuevo movimiento de Cashflow', $r('web.cashflow.movimiento')],
    ['OPERATIVO', 'Caja abierta', ($ab = CajaOperativa::where('estado', 'ABIERTA')->value('id')) ? $r('web.caja.detalle', $ab) : null],
    // Días 29 a 33: Configuración (recargo por mora), la ficha de un moroso con recargo, el cobro con recargo,
    // el historial de una corrección de Cashflow y la caja rechazada que se corrige.
    ['ADMIN', 'Configuración', $r('web.configuraciones.index')],
    ['ADMIN', 'Ficha de un moroso con recargo', ($m = DB::table('cargos_alumno')->where('tipo', 'PUNITORIO')->where('estado', 'VIGENTE')->orderBy('id')->value('alumno_id')) ? $r('web.alumnos.show', $m) : null],
    ['OPERATIVO', 'Cobrar a un moroso con recargo', $m ? $r('web.caja.cobrar', $m) : null],
    ['ADMIN', 'Historial de una corrección de Cashflow', ($cc = DB::table('cashflow_correcciones')->orderBy('id')->value('movimiento_id')) ? $r('web.cashflow.movimientos.show', $cc) : null],
    ['ADMIN', 'Caja rechazada o corregida', ($rz = CajaOperativa::whereNotNull('motivo_rechazo')->orderByDesc('id')->value('id')) ? $r('web.caja.detalle', $rz) : null],
    // Días 34 a 40: Cobranza filtrada por morosos, la ficha de quien tiene dos fichas, el alta y la edición con
    // cambio de grupo, Cobrar con un adelanto, Transferir, y la ficha de la clase cancelada.
    ['ADMIN', 'Cobranza · morosos', $r('web.cobranza.index', ['estado' => 'MOROSO'])],
    ['OPERATIVO', 'Ficha de quien tiene dos fichas', ($dos = DB::table('alumnos')->select('nombre', 'apellido')->groupBy('nombre', 'apellido')->havingRaw('COUNT(*) > 1')->first())
        ? $r('web.alumnos.show', Alumno::where('nombre', $dos->nombre)->where('apellido', $dos->apellido)->orderBy('id')->value('id')) : null],
    ['OPERATIVO', 'Alta de alumno', $r('web.alumnos.create')],
    ['ADMIN', 'Editar alumno (cambio de grupo)', ($cg = DB::table('alumno_planes')->where('fecha_desde', '>', now()->toDateString())->where('activo', 1)->value('alumno_id')) ? $r('web.alumnos.edit', $cg) : null],
    ['OPERATIVO', 'Cobrar a quien adelantó un mes', ($ad = DB::table('deuda_cuotas')->where('periodo', '>', $mes)->orderByDesc('id')->value('alumno_id')) ? $r('web.caja.cobrar', $ad) : null],
    ['ADMIN', 'Transferir entre cuentas', $r('web.cashflow.transferencia')],
    ['ADMIN', 'Ficha de una clase cancelada', ($cc2 = Clase::where('cancelada', 1)->orderByDesc('fecha')->value('id')) ? $r('web.clases.show', $cc2) : null],
    ['OPERATIVO', 'Movimientos', $r('web.movimientos.index')],
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
    // Si la operativa no tiene un turno abierto, Cobrar manda a abrir caja y no se mide nada. Se abre uno dentro
    // de esta transacción, que se revierte al final: no queda en la base.
    if ($oper && !CajaOperativa::where('usuario_operativo_id', $oper->id)->where('estado', 'ABIERTA')->exists()) {
        DB::table('cajas_operativas')->insert(['usuario_operativo_id' => $oper->id, 'estado' => 'ABIERTA', 'apertura_at' => now(),
            'tipo_caja_efectivo_id' => app(App\Services\CajaService::class)->tipoCajaMostradorId(), 'efectivo_heredado' => 0, 'efectivo_inicial' => 0,
            'efectivo_esperado' => 0, 'usuario_apertura_id' => $oper->id, 'created_at' => now(), 'updated_at' => now()]);
        $pantallas[] = ['OPERATIVO', 'Caja abierta (turno de medición)', $r('web.caja.detalle', DB::table('cajas_operativas')->max('id'))];
    }
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
