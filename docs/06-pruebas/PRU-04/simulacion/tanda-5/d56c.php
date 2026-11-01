<?php
// Día 56, viernes 4/12, 17:00: Lucía toma Intermedias. Alma vuelve debiendo, con motivo.
require __DIR__.'/lib.php';
use App\Http\Controllers\ClaseWebController;
use App\Models\{Alumno, Clase};
$lu = U('Lucía Gaitán'); $cc = app(ClaseWebController::class);
$c = Clase::whereDate('fecha', '2026-12-04')->whereTime('hora_inicio', '16:00:00')->where('grupo_id', 2)->firstOrFail();
$lista = function (array $mot) use ($lu, $cc, $c) {
    $items = Alumno::enGrupoElDia($c->grupo_id, $c->fecha)->where('activo', true)->get()->map(function ($a) use ($mot) {
        $n = "{$a->apellido}, {$a->nombre}"; return ['alumno_id' => $a->id, 'presente' => in_array($n, ['Sosa, Rocio'], true) ? 0 : 1, 'motivo_exceso' => 'EXTRA'] + (isset($mot[$n]) ? ['motivo_excepcion' => $mot[$n]] : []);
    })->all();
    return enviar($lu, 'POST', "/clases/{$c->id}/asistencias", ['items' => $items], fn ($r) => $cc->storeAsistencias($r, $c->id));
};
paso('56.9a', 'Lucía, Alma presente sin motivo', $lista([]), 422);
paso('56.9b', 'con el motivo de Alma', $lista(['Navarro, Alma' => 'Vuelve hoy: la mamá paga el sábado']), 200);
