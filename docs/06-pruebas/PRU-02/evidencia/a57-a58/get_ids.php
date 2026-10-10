<?php
require __DIR__ . '/../../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'alumnoId' => App\Models\Alumno::first()?->id,
    'claseId' => App\Models\Clase::first()?->id,
    'grupoId' => App\Models\Grupo::first()?->id,
    'profesorId' => App\Models\Profesor::first()?->id,
    'rubroId' => App\Models\Rubro::first()?->id,
    'subrubroId' => App\Models\Subrubro::first()?->id,
    'tipoCajaId' => App\Models\TipoCaja::first()?->id,
    'cajaId' => App\Models\CajaOperativa::first()?->id,
    'usuarioId' => App\Models\User::first()?->id,
    'deporteId' => App\Models\Deporte::first()?->id,
    'nivelId' => App\Models\Nivel::first()?->id,
    'liquidacionId' => App\Models\Liquidacion::first()?->id,
], JSON_PRETTY_PRINT);
