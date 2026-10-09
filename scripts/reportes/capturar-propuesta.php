<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Solo se permite wings_testing_codex.\n");
    exit(1);
}
if (\App\Models\PrimeraCarga::pendiente()) {
    fwrite(STDERR, "Completar el escenario ficticio antes de capturar.\n");
    exit(1);
}
config(['session.driver' => 'array', 'session.connection' => null, 'cache.default' => 'array']);
$servicio = app(\App\Services\ReporteMensualService::class);
$reporte = $servicio->obtener(today()->format('Y-m'));
$evolucion = $servicio->evolucion($reporte['mes']);
$visual = in_array('--visual', $argv, true);
$vista = $visual ? 'propuesta-finanzas-visual.blade.php' : 'propuesta-finanzas.blade.php';
\Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'ensure.active.web', 'ensure.admin.web'])
    ->get('/__codex/propuesta-finanzas', fn () => view()->file(base_path('docs/06-pruebas/B12-A23/'.$vista),
        ['reporte' => $reporte, 'evolucion' => $evolucion]));
\Illuminate\Support\Facades\Auth::setUser(\App\Models\User::where('rol', 'ADMIN')->firstOrFail());
$respuesta = $app->make(\Illuminate\Contracts\Http\Kernel::class)->handle(\Illuminate\Http\Request::create('/__codex/propuesta-finanzas'));
if ($respuesta->getStatusCode() !== 200) {
    fwrite(STDERR, 'La solicitud a Laravel falló: '.$respuesta->getStatusCode()."\n");
    exit(1);
}
$html = preg_replace([
    '/(<meta name="csrf-token" content=")[^"]+/', '/(name="_token" value=")[^"]+/',
], ['$1FICTICIO', '$1FICTICIO'], $respuesta->getContent());
$html = preg_replace('#https?://[^/]+/build/#', '../../../../public/build/', $html);
$html = preg_replace('#https?://[^/]+/img/#', '../../../../public/img/', $html);
$html = preg_replace('/[ \t]+(?=\r?$)/m', '', $html);
file_put_contents(base_path('docs/06-pruebas/B12-A23/capturas/finanzas-'.($visual ? 'v3' : 'v2').'.html'), $html);
echo "Propuesta financiera: HTML de Laravel autenticado guardado, tokens ficticios.\n";
