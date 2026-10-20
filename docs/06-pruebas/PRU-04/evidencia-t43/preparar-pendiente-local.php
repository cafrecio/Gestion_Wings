<?php

require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex_t61') {
    throw new RuntimeException('Solo base de capturas ficticia.');
}

use App\Models\{Clase, Profesor, Rubro, Subrubro};
use App\Services\LiquidacionService;

$base = Profesor::where('dni', '32990043')->firstOrFail();
$manual = in_array('--manual', $argv, true);
$cabecera = in_array('--cabecera', $argv, true);
$dniPrueba = $cabecera ? '32990046' : ($manual ? '32990045' : '32990044');
$profesor = Profesor::where('dni', $dniPrueba)->first();
if (!$profesor) {
    $profesor = $base->replicate(['subrubro_id']);
    $profesor->dni = $dniPrueba;
    $profesor->apellido = $cabecera ? 'Cabecera' : ($manual ? 'Prueba manual' : 'Pendiente');
    $profesor->save();
    foreach (Clase::whereHas('profesores', fn ($q) => $q->where('profesores.id', $base->id))->get() as $clase) {
        $clase->profesores()->syncWithoutDetaching([$profesor->id]);
    }
    $liq = app(LiquidacionService::class)->generarLiquidacionMensual($profesor->id, 9, 2026);
} else {
    $liq = $profesor->liquidaciones()->where('mes', 9)->where('anio', 2026)->firstOrFail();
}
if ($manual && !$profesor->subrubro_id) {
    $rubro = Rubro::firstOrCreate(['nombre' => 'Sueldos de prueba'], ['tipo' => 'EGRESO']);
    $subrubro = Subrubro::firstOrCreate(['rubro_id' => $rubro->id, 'nombre' => 'Sueldo - Prueba manual, Docente'],
        ['permitido_para' => 'ADMIN', 'afecta_caja' => false, 'activo' => true]);
    $profesor->update(['subrubro_id' => $subrubro->id]);
}
echo json_encode(['liquidacion_id' => $liq->id, 'original' => $liq->total_calculado, 'confirmado' => $liq->total_confirmado]);
