<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex' || \App\Models\PrimeraCarga::pendiente()) {
    fwrite(STDERR, "Solo se permite el escenario completo en wings_testing_codex.\n");
    exit(1);
}
config(['session.driver' => 'array', 'session.connection' => null, 'cache.default' => 'array']);
\Illuminate\Support\Facades\Auth::setUser(\App\Models\User::where('rol', 'ADMIN')->firstOrFail());
$solicitudes = [
    'finanzas-aplicado' => '/reportes',
    'finanzas-septiembre' => '/reportes?mes=2026-09',
    'finanzas-deporte' => '/reportes?mes=2026-09&deporte_id='.\App\Models\Alumno::firstOrFail()->deporte_id,
    'inicio-con-reportes' => '/admin/dashboard',
];
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
