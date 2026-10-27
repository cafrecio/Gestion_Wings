<?php

require __DIR__.'/../evidencia-verificacion-t92-codex/bootstrap.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDatabaseName() !== 'wings_testing_codex') throw new RuntimeException('Base incorrecta.');
$fixture = json_decode(file_get_contents(__DIR__.'/fixture-navegador.json'), true, flags: JSON_THROW_ON_ERROR);
$pagos = App\Models\Pago::orderBy('id')->get(['id', 'alumno_id', 'monto_final', 'estado']);
$excepciones = App\Models\ExcepcionCobranza::orderBy('id')->get();
$movimientos = App\Models\MovimientoOperativo::orderBy('id')->get(['id', 'monto', 'subrubro_id', 'tipo_caja_id', 'estado']);
$deudas = App\Models\DeudaCuota::orderBy('alumno_id')->orderBy('periodo')->get(['alumno_id', 'periodo', 'monto_original', 'monto_pagado', 'estado']);
$arqueo = app(App\Services\CajaService::class)->arqueoCaja(App\Models\CajaOperativa::sole()->id);
if ($pagos->count() !== 2 || (float) $pagos->sum('monto_final') !== 38000.0 || $excepciones->count() !== 2
    || $movimientos->count() !== 2 || $arqueo['efectivo_esperado'] !== '39000.00'
    || App\Models\MovimientoOperativo::where('subrubro_id', $fixture['gasto'])->exists()) {
    throw new RuntimeException('El recorrido no coincide con los dos cobros confirmados y gasto rechazado.');
}
$parcial = $deudas->firstWhere('alumno_id', $fixture['alumnos']['Parcial']);
if ($parcial->monto_pagado !== '10000.00') throw new RuntimeException('Volver debia dejar sin cobrar la segunda seña de5000.');
foreach ($deudas->where('alumno_id', $fixture['alumnos']['Anteriores'])->whereIn('periodo', ['2026-03', '2026-05']) as $d) {
    if ($d->monto_pagado !== '0.00') throw new RuntimeException('Se altero una deuda anterior.');
}
file_put_contents(__DIR__.'/resultado-navegador.json', json_encode(['base' => $db->getDatabaseName(), 'pagos' => $pagos,
    'excepciones' => $excepciones, 'movimientos' => $movimientos, 'deudas' => $deudas, 'arqueo' => $arqueo,
    'comprobado' => ['Parcial de10000 confirmado con motivo', 'Octubre28000 confirmado dejando marzo/mayo56000 intactos',
        'Volver sobre la seña de5000 no guardo', 'Gasto40000 rechazado con efectivo39000', 'Inicio muestra ambas excepciones'],
    'limites' => 'Solo datos sinteticos locales. Primer intento de tipeo incorrecto fue descartado reiniciando el fixture. Aviso de gasto conserva cierre automatico estandar de3s.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'Recorrido: dos pagos38000, dos excepciones, ningun gasto y efectivo39000; anteriores intactos.'.PHP_EOL;
