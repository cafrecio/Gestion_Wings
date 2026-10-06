<?php
require getcwd().'/vendor/autoload.php';
$app=require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!$app->environment('testing') || Illuminate\Support\Facades\DB::connection()->getDatabaseName()!=='wings_testing_codex') exit(1);
$estado=[
    'pagos'=>App\Models\Pago::orderBy('id')->get(['id','alumno_id','monto_final','estado','detalle_anulacion'])->toArray(),
    'cajas'=>App\Models\CajaOperativa::get(['id','usuario_operativo_id','estado','efectivo_inicial'])->toArray(),
    'movimientos'=>App\Models\MovimientoOperativo::get()->toArray(),
    'arqueo'=>app(App\Services\CajaService::class)->arqueoCaja(1),
];
file_put_contents(__DIR__.'/estado-tras-pantalla.json',json_encode($estado,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
echo json_encode($estado,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
