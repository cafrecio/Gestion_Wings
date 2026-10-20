<?php

// Solo datos ficticios en la base de capturas ya inicializada; nunca la base del club.
require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex_t61') {
    throw new RuntimeException('Esta preparación solo admite wings_testing_codex_t61.');
}

use App\Models\{Alumno, Asistencia, Clase, Deporte, DeudaCuota, Grupo, Nivel, Pago, Profesor};
use App\Services\LiquidacionService;

$existente = Profesor::where('dni', '32990043')->first();
if ($existente) {
    echo json_encode(['profesor_id' => $existente->id, 'liquidaciones' => $existente->liquidaciones()->pluck('id')]);
    exit;
}
Illuminate\Support\Facades\DB::transaction(function () {
    $d = Deporte::create(['nombre' => 'Prueba de comisión', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    $n = Nivel::create(['nombre' => 'Prueba T43']);
    $g = Grupo::create(['deporte_id' => $d->id, 'nivel_id' => $n->id, 'activo' => true]);
    $prof = Profesor::create(['deporte_id' => $d->id, 'nombre' => 'Docente', 'apellido' => 'Demostración',
        'dni' => '32990043', 'fecha_nacimiento' => '1985-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba',
        'porcentaje_comision' => 40, 'activo' => true]);
    $a = Alumno::create(['deporte_id' => $d->id, 'grupo_id' => $g->id, 'nombre' => 'Alumno', 'apellido' => 'Ejemplo',
        'dni' => '45990043', 'fecha_nacimiento' => '2010-01-01', 'celular' => '1100000043', 'fecha_alta' => '2026-01-01', 'activo' => true]);
    foreach (['2026-09', '2026-10'] as $periodo) {
        $c = Clase::create(['grupo_id' => $g->id, 'fecha' => $periodo.'-10', 'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00', 'cancelada' => false]);
        $c->profesores()->attach($prof->id);
        Asistencia::create(['clase_id' => $c->id, 'alumno_id' => $a->id, 'presente' => true]);
    }
    $p = Pago::create(['alumno_id' => $a->id, 'mes' => 9, 'anio' => 2026, 'fecha_pago' => '2026-09-15',
        'monto_base' => 260000, 'monto_final' => 260000, 'monto_cuota' => 260000, 'porcentaje_aplicado' => 100, 'estado' => 'COMPLETADO']);
    foreach (['2026-07' => 30000, '2026-08' => 50000, '2026-09' => 100000, '2026-10' => 80000] as $periodo => $importe) {
        $deuda = DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => $importe, 'monto_pagado' => $importe, 'estado' => 'PAGADA']);
        $p->deudasCuota()->attach($deuda->id, ['monto_aplicado' => $importe]);
    }
    $s = app(LiquidacionService::class);
    $sept = $s->generarLiquidacionMensual($prof->id, 9, 2026);
    $oct = $s->generarLiquidacionMensual($prof->id, 10, 2026);
    echo json_encode(['profesor_id' => $prof->id, 'septiembre_id' => $sept->id, 'octubre_id' => $oct->id,
        'septiembre_original' => $sept->total_calculado, 'septiembre_confirmado' => $sept->total_confirmado,
        'octubre_adelantada' => $oct->total_calculado]);
});
