<?php
// Ensayo: corre la generación del 1/12 sobre la foto del día 47 y compara cada persona con lo que se calculó antes de simular.
// No reemplaza a la tanda 5: entre el 26 y el 30/11 todavía hay clases. Sirve para saber si lo preparado da lo que tiene que dar.
require __DIR__.'/lib.php';
use App\Models\{Alumno, DeudaCuota};
$estadoDe = function (Alumno $a) {
    if (DB::table('alumnos_revision_cobranza')->where('alumno_id', $a->id)->where('periodo_objetivo', '2026-12')->exists()) return 'REVISION';
    $d = DeudaCuota::where('alumno_id', $a->id)->where('periodo', '2026-12')->first();
    return $d ? "CUOTA {$d->estado} ".plata($d->monto_original).' pagado '.plata($d->monto_pagado) : 'SIN CUOTA';
};
$ficha = fn (string $ap, string $no, string $niv) => Alumno::where('apellido', $ap)->where('nombre', $no)->whereHas('grupo', fn ($g) => $g->whereHas('nivel', fn ($n) => $n->where('nombre', $niv)))->firstOrFail();
Artisan::call('cobranza:generar-deudas');
$g = DB::table('generaciones_cuotas')->where('periodo', '2026-12')->first();
echo 'constancia: creadas '.$g->creadas.' · a revisión '.$g->a_revision.' · ya existían '.$g->ya_existian.' · sin plan '.$g->sin_plan.' · activos '.Alumno::where('activo', 1)->count()."\n";
$casos = [
 ['Ramos','Pilar','Principiantes','REVISION'], ['Navarro','Alma','Intermedias','REVISION'], ['Suarez','Valentina','Avanzadas','REVISION'], ['Villalba','Emma','Intermedias','REVISION'],
 ['Luna','Joaquina','Adultos','REVISION'], ['Vera','Tobías','Principiantes','REVISION'], ['Ruiz','Thiago','Principiantes','REVISION'],
 ['Godoy','Valentino','Principiantes','CUOTA'], ['Cabrera','Bruno','Adultos','CUOTA'], ['Ruiz','Thiago','Principiantes','REVISION'],
 ['Acuña','Renzo','Infantiles','CUOTA'], ['Iglesias','Clara','Principiantes','CUOTA'], ['Rios','Facundo','Principiantes','CUOTA'], ['Medina','Emilia','Principiantes','CUOTA'],
 ['Pereyra','Liam','Principiantes','CUOTA'], ['Ferreyra','Milagros','Avanzadas','CUOTA'], ['Quiroga','Lara','Intermedias','CUOTA'], ['Sosa','Bautista','Principiantes','CUOTA'],
];
foreach ($casos as [$ap,$no,$niv,$esp]) {
    $a = Alumno::where('apellido', $ap)->where('nombre', $no)->whereHas('grupo', fn ($g) => $g->whereHas('nivel', fn ($n) => $n->where('nombre', $niv)))->first();
    if (!$a) { echo "   $ap, $no ($niv): no hay ficha\n"; continue; }
    $r = $estadoDe($a);
    echo sprintf("   %s, %s (%s): %s%s\n", $ap, $no, $niv, $r, str_starts_with($r, $esp) ? '' : " <<< NO ESPERADO (calculé $esp)");
}
foreach ([['Ruiz','Thiago'],['Medina','Lautaro']] as [$ap,$no]) foreach (Alumno::where('apellido',$ap)->where('nombre',$no)->get() as $a) echo "   $ap, $no ficha #{$a->id} activo {$a->activo} grupo {$a->grupo_id}: ".$estadoDe($a)."\n";
$c = app(App\Services\CobranzaEstadoService::class)->resumenDashboard();
echo 'Cobranza 1/12: deuda '.plata($c['total_adeudado']).' · '.json_encode($c['por_estado'])."\n";
$vol = fn ($t) => DB::table($t)->count();
echo 'Revisión pendientes: '.DB::table('alumnos_revision_cobranza')->where('estado_revision', 'PENDIENTE')->count()."\n";
