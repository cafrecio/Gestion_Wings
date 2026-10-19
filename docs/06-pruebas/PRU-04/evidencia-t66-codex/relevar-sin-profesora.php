<?php
// Solo lectura: no incluye nombres, DNI ni credenciales.
$repo = getenv('T66_REPO') ?: dirname(__DIR__, 4);
require $repo.'/vendor/autoload.php';
$app = require $repo.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if (!in_array($db->getDatabaseName(), ['wings_testing_codex', 'wings'], true)) {
    throw new RuntimeException('Base no habilitada para este relevamiento.');
}
$db->statement('START TRANSACTION READ ONLY');
try {
    $query = App\Models\Clase::doesntHave('profesores');
    $muestra = (clone $query)->orderBy('id')->limit(5)->get(['id', 'fecha', 'cancelada'])->toArray();
    $ids = (clone $query)->pluck('id');
    $detalles = App\Models\LiquidacionDetalle::where('tipo_referencia', App\Models\LiquidacionDetalle::TIPO_CLASE)
        ->whereIn('referencia_id', $ids)->with('liquidacion')->get();
    echo json_encode(['fecha_consulta' => now()->toIso8601String(), 'base' => $db->getDatabaseName(),
        'muestra' => $muestra, 'total' => $ids->count(),
        'pasadas' => (clone $query)->whereDate('fecha', '<', today())->count(),
        'hoy_y_futuras' => (clone $query)->whereDate('fecha', '>=', today())->count(),
        'canceladas' => (clone $query)->where('cancelada', true)->count(),
        'detalles_hora' => $detalles->map(fn ($d) => ['clase_id' => $d->referencia_id,
            'liquidacion_id' => $d->liquidacion_id, 'estado' => $d->liquidacion->estado,
            'estado_pago' => $d->liquidacion->estado_pago])->all(),
        'limite_comision' => 'Sin profesoras asignadas no hay vínculo actual clase-profesora para atribuir comisión; no se reconstruye historia.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} finally {
    $db->rollBack();
}
