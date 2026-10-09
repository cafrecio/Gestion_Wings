<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex' || \App\Models\PrimeraCarga::pendiente()) {
    fwrite(STDERR, "Solo se permite el escenario completo en wings_testing_codex.\n");
    exit(1);
}
config(['session.driver' => 'array', 'session.connection' => null, 'cache.default' => 'array']);
// El escenario registra sus cierres a las18; consultar ese mismo instante ficticio.
\Carbon\Carbon::setTestNow('2026-10-09 18:00:00');
\Illuminate\Support\Facades\Auth::setUser(\App\Models\User::where('rol', 'ADMIN')->firstOrFail());
$solicitudes = [
    'finanzas-aplicado' => '/reportes',
    'finanzas-septiembre' => '/reportes?mes=2026-09',
    'finanzas-deporte' => '/reportes?mes=2026-09&deporte_id='.\App\Models\Alumno::firstOrFail()->deporte_id,
    'inicio-con-reportes' => '/admin/dashboard',
    'alumnos-aplicado' => '/reportes/alumnos',
    'alumnos-aplicado-septiembre' => '/reportes/alumnos?mes=2026-09',
    'alumnos-aplicado-deporte' => '/reportes/alumnos?mes=2026-09&deporte_id='.\App\Models\Alumno::firstOrFail()->deporte_id,
];
$solicitudes += [
    'sueldos-aplicado' => '/reportes/sueldos',
    'sueldos-septiembre' => '/reportes/sueldos?mes=2026-09',
    'sueldos-deporte' => '/reportes/sueldos?mes=2026-09&deporte_id='.\App\Models\Alumno::firstOrFail()->deporte_id,
    'sueldos-sin-historial' => '/reportes/sueldos?mes=2026-03',
    'liquidaciones-final' => '/liquidaciones',
];
foreach (['PENDIENTE'=>'ajuste-final','PAGADA'=>'ajuste-pagado'] as $estado=>$nombre) {
    $l = \App\Models\Liquidacion::where('tipo','COMISION')->where('estado','CERRADA')->where('estado_pago',$estado)->whereNotNull('monto_final')->firstOrFail();
    $solicitudes[$nombre] = '/liquidaciones/'.$l->id;
}
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
foreach ($solicitudes as $nombre => $ruta) {
    $request = \Illuminate\Http\Request::create($ruta);
    $respuesta = $kernel->handle($request);
    if ($respuesta->getStatusCode() !== 200) {
        fwrite(STDERR, $nombre.': HTTP '.$respuesta->getStatusCode()."\n");
        exit(1);
    }
    $html = preg_replace(['/(<meta name="csrf-token" content=")[^"]+/', '/(name="_token" value=")[^"]+/'], ['$1FICTICIO', '$1FICTICIO'], $respuesta->getContent());
    $html = preg_replace('#https?://[^/]+/build/#', '../../../../public/build/', $html);
    $html = preg_replace('#https?://[^/]+/img/#', '../../../../public/img/', $html);
    $html = preg_replace('/[ \t]+(?=\r?$)/m', '', $html);
    file_put_contents(base_path('docs/06-pruebas/B12-A23/capturas/'.$nombre.'.html'), $html);
    $kernel->terminate($request, $respuesta);
    echo $nombre.": HTTP200 real, HTML guardado.\n";
}
$pagada = \App\Models\Liquidacion::where('tipo','COMISION')->where('estado_pago','PAGADA')->whereNotNull('monto_final')->firstOrFail();
$rutaPdf = app(\App\Services\ReciboService::class)->generarReciboLiquidacion($pagada->id,true);
file_put_contents(base_path('docs/06-pruebas/B12-A23/capturas/recibo-ajuste-final.pdf'),\Illuminate\Support\Facades\Storage::get($rutaPdf));
echo "Recibo real del escenario ficticio guardado.\n";
