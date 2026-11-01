<?php
// Día 54, miércoles 2/12, 19:00: Hernán toma Fútbol Principiantes por teléfono. Cierre de la tanda.
require __DIR__.'/lib.php';
use App\Http\Controllers\ClaseWebController;
use App\Models\{Alumno, Clase};
$he = U('Hernán Quintana'); $cc = app(ClaseWebController::class);
$c = Clase::whereDate('fecha', '2026-12-02')->whereTime('hora_inicio', '18:00:00')->where('grupo_id', 5)->firstOrFail();
$mot = ['Pereyra, Liam' => 'El papá avisó que paga esta semana', 'Ruiz, Thiago' => 'Viene a Fútbol: arregla lo de Patín', 'Vera, Tobías' => 'La mamá paga esta semana'];
$items = Alumno::enGrupoElDia($c->grupo_id, $c->fecha)->where('activo', true)->get()->map(function ($a) use ($mot) {
    $n = "{$a->apellido}, {$a->nombre}"; return ['alumno_id' => $a->id, 'presente' => in_array($n, ['Rios, Facundo'], true) ? 0 : 1, 'motivo_exceso' => 'EXTRA'] + (isset($mot[$n]) ? ['motivo_excepcion' => $mot[$n]] : []);
})->all();
echo '   alumnos de la lista: '.count($items)."\n";
paso('54.8', 'Hernán toma Fútbol Principiantes', enviar($he, 'POST', "/clases/{$c->id}/asistencias", ['items' => $items], fn ($r) => $cc->storeAsistencias($r, $c->id)), 200);
echo '   revisiones pendientes: '.DB::table('alumnos_revision_cobranza')->where('estado_revision', 'PENDIENTE')->count()."\n";
$cob = app(App\Services\CobranzaEstadoService::class);
foreach ($cob->listadoCobranza('DEUDOR') as $f) echo "   Deudor: {$f->apellido}, {$f->nombre} ".plata($f->deuda_total)."\n";
foreach (['2026-10', '2026-11', '2026-12'] as $p) { $r = app(App\Services\ReporteMensualService::class)->obtener($p); echo "   $p: ingresos ".plata($r['ingresos'] / 100).' · egresos '.plata($r['egresos'] / 100).' · resultado '.plata(($r['ingresos'] - $r['egresos']) / 100)."\n"; }
$vol = fn ($t) => DB::table($t)->count();
echo '   volumen: movimientos de caja '.$vol('movimientos_operativos').' · cobros '.$vol('pagos').' · marcas '.$vol('asistencias').' · Cashflow '.$vol('cashflow_movimientos').' · alumnos '.$vol('alumnos').' · liquidaciones '.$vol('liquidaciones')."\n";
