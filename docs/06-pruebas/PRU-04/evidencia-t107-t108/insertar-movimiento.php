<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sub = App\Models\Subrubro::first()->id;
$tipo = App\Models\TipoCaja::where('nombre', 'Efectivo')->first()->id;

App\Models\MovimientoOperativo::create([
    'caja_operativa_id' => 1,
    'fecha' => today(),
    'tipo_caja_id' => $tipo,
    'subrubro_id' => $sub,
    'monto' => 44000,
    'usuario_id' => 2,
    'estado' => 'ACTIVO',
    'observaciones' => 'Cobro intermedio',
]);

echo "OK\n";
