<?php
/**
 * PRU-04 · Compara lo que generó Wings para noviembre con la lista esperada (esperado.json), alumno por alumno.
 * Solo lectura. Uso: php comparar-generacion.php /home/wingstest/app ruta/esperado.json
 * Imprime: coinciden, sobran (Wings generó y no tocaba), faltan (tocaba y no generó), importe distinto,
 * y la cola de Revisión de noviembre. Sale con 0 solo si todo coincide.
 */
$raiz = $argv[1] ?? '/home/wingstest/app';
$esperado = json_decode(file_get_contents($argv[2] ?? __DIR__.'/esperado.json'), true);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (config('database.connections.mysql.database') !== 'wingstest') { fwrite(STDERR, "Solo wingstest.\n"); exit(2); }

$periodo = $esperado['periodo'];
$quiere = [];
foreach ($esperado['filas'] as $f) if ($f['resultado'] === 'CUOTA') $quiere[$f['id']] = $f;
$yaTenia = array_column(array_filter($esperado['filas'], fn ($f) => $f['resultado'] === 'YA_TIENE'), 'alumno', 'id');

// Lo generado: cuotas de noviembre que no existían cuando se armó la lista (las de adelanto ya estaban).
$hay = [];
foreach (DB::select("select dc.alumno_id, dc.monto_original, concat(a.apellido, ', ', a.nombre) alumno from deuda_cuotas dc
    join alumnos a on a.id = dc.alumno_id where dc.periodo = ?", [$periodo]) as $r) {
    if (!isset($yaTenia[$r->alumno_id])) $hay[$r->alumno_id] = $r;
}
$ok = $sobran = $faltan = $distinto = [];
foreach ($quiere as $id => $f) {
    if (!isset($hay[$id])) { $faltan[] = "{$f['alumno']} ({$f['grupo']}, \${$f['importe']})"; continue; }
    if ((int) round((float) $hay[$id]->monto_original) !== $f['importe']) $distinto[] = "{$f['alumno']}: Wings \$".(int) $hay[$id]->monto_original.", esperado \${$f['importe']}";
    else $ok[] = $id;
}
foreach ($hay as $id => $r) if (!isset($quiere[$id])) $sobran[] = "{$r->alumno} (\$".(int) $r->monto_original.')';
$dobles = DB::select("select alumno_id, count(*) n from deuda_cuotas where periodo = ? group by alumno_id having n > 1", [$periodo]);
$revision = DB::select("select concat(a.apellido, ', ', a.nombre) alumno, r.estado_revision from alumnos_revision_cobranza r
    join alumnos a on a.id = r.alumno_id where r.periodo_objetivo = ?", [$periodo]);
$generado = DB::selectOne("select count(*) n from generaciones_cuotas where periodo = ?", [$periodo]);

printf("Período %s · generación registrada: %s\n", $periodo, $generado && $generado->n ? 'sí' : 'NO');
printf("Coinciden %d de %d · total generado \$%s (esperado \$%s)\n", count($ok), count($quiere),
    number_format(array_sum(array_map(fn ($r) => (float) $r->monto_original, $hay)), 0, ',', '.'), number_format($esperado['total_cuotas'], 0, ',', '.'));
foreach (['Sobran' => $sobran, 'Faltan' => $faltan, 'Importe distinto' => $distinto] as $t => $l) printf("%s: %d%s\n", $t, count($l), $l ? "\n  - ".implode("\n  - ", $l) : '');
printf("Cuotas duplicadas: %d\n", count($dobles));
printf("Revisión de %s: %d%s\n", $periodo, count($revision), $revision ? ' — '.implode('; ', array_map(fn ($r) => "{$r->alumno} ({$r->estado_revision})", $revision)) : '');
exit(($sobran || $faltan || $distinto || $dobles || count($ok) !== count($quiere)) ? 1 : 0);
