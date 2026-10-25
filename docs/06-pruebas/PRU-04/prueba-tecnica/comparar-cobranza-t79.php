<?php
$base = 'C:/xampp/htdocs/gestion-wings';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') exit(2);
$s = app(App\Services\CobranzaEstadoService::class);
$campos = ['id', 'estado_cobranza', 'deuda_total', 'deuda_cuotas', 'inscripcion_pendiente', 'punitorios_pendientes', 'deuda_mas_antigua', 'cantidad_cuotas_impagas', 'ayuda_otro_deporte'];
$out = [];
foreach (['TODOS', 'DEUDORES', null] as $f) $out['listado_'.($f ?? 'null')] = $s->listadoCobranza($f)->map(fn ($a) => $a->only($campos))->all();
$out['resumen'] = $s->resumenDashboard();
$out['saldos_sin_relacion'] = $s->saldoDeAlumnos(App\Models\Alumno::where('activo', true)->get());
file_put_contents($argv[1], json_encode($out, JSON_PRETTY_PRINT));
$t = []; for ($i = 0; $i < 4; $i++) { $x = microtime(true); $s->listadoCobranza('DEUDORES'); $t[] = (microtime(true) - $x) * 1000; }
printf("filas TODOS %d · listado min %.0f ms\n", count($out['listado_TODOS']), min($t));
