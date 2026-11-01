<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$alumnosThiago = App\Models\Alumno::where('dni', '45000001')->get();
$cargosThiago = App\Models\CargoAlumno::whereIn('alumno_id', $alumnosThiago->pluck('id'))->get();
$caja = App\Models\CajaOperativa::find(1);

echo json_encode([
    'alumnos_thiago' => $alumnosThiago->count(),
    'deportes' => $alumnosThiago->pluck('deporte.nombre')->all(),
    'cargos_inscripcion' => $cargosThiago->where('tipo', 'INSCRIPCION')->count(),
    'caja_estado' => $caja->estado,
    'efectivo_esperado' => (float)$caja->efectivo_esperado,
    'efectivo_contado' => (float)$caja->efectivo_contado,
    'diferencia_efectivo' => (float)$caja->diferencia_efectivo,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
