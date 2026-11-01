<?php
// Día 55, jueves 3/12, 16:30: Nora toma su primera lista de Hockey por teléfono.
require __DIR__.'/lib.php';
use App\Http\Controllers\ClaseWebController;
use App\Models\{Alumno, Clase, Grupo};
$no = U('Nora Vidal'); $cc = app(ClaseWebController::class);
$gh = Grupo::whereHas('deporte', fn ($q) => $q->where('nombre', 'Hockey'))->firstOrFail();
$c = Clase::whereDate('fecha', '2026-12-03')->where('grupo_id', $gh->id)->firstOrFail();
$items = Alumno::enGrupoElDia($c->grupo_id, $c->fecha)->where('activo', true)->get()->map(fn ($a) => ['alumno_id' => $a->id, 'presente' => 1, 'motivo_exceso' => 'EXTRA'])->all();
echo '   alumnos de la lista: '.count($items)."\n";
paso('55.8', 'Nora toma la lista', enviar($no, 'POST', "/clases/{$c->id}/asistencias", ['items' => $items], fn ($r) => $cc->storeAsistencias($r, $c->id)), 200);
[$st, $t] = http($no, '/clases'); echo "   Nora ve Clases: $st $t\n";
[$st, $t] = http($no, '/alumnos'); echo "   Nora escribe Alumnos: $st $t\n";
