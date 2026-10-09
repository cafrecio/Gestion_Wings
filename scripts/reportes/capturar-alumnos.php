<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex' || \App\Models\PrimeraCarga::pendiente()) {
    fwrite(STDERR, "Solo escenario completo en wings_testing_codex.\n"); exit(1);
}
config(['session.driver'=>'array', 'session.connection'=>null, 'cache.default'=>'array']);
\Illuminate\Support\Facades\Route::middleware(['web','auth','ensure.active.web','ensure.admin.web'])
    ->get('/__codex/propuesta-alumnos', function (\Illuminate\Http\Request $request) {
        $datos = $request->validate(['mes'=>['sometimes','required','date_format:Y-m','before_or_equal:'.today()->format('Y-m')],
            'deporte_id'=>['nullable','integer','exists:deportes,id']]);
        $mes = $datos['mes'] ?? today()->format('Y-m');
        $servicio = app(\App\Services\ReporteAlumnosService::class);
        $reporte = $servicio->obtener($mes, isset($datos['deporte_id']) ? (int) $datos['deporte_id'] : null);
        $evolucion = $servicio->evolucion($mes, $reporte['deporte_id']);
        $deportes = \App\Models\Deporte::orderBy('nombre')->get(['id','nombre']);
        $meses = [];
        for ($p = \Carbon\CarbonImmutable::parse(today()->format('Y-m').'-01'); $p->format('Y-m') >= '2026-04'; $p = $p->subMonth()) $meses[] = $p->format('Y-m');
        return view()->file(base_path('docs/06-pruebas/B12-A23/propuesta-alumnos.blade.php'), compact('reporte','evolucion','deportes','meses'));
    });
\Illuminate\Support\Facades\Auth::setUser(\App\Models\User::where('rol','ADMIN')->firstOrFail());
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
foreach (['alumnos-propuesta'=>'', 'alumnos-septiembre'=>'?mes=2026-09',
    'alumnos-deporte'=>'?deporte_id='.\App\Models\Alumno::firstOrFail()->deporte_id] as $nombre=>$parametros) {
    $request = \Illuminate\Http\Request::create('/__codex/propuesta-alumnos'.$parametros);
    $respuesta = $kernel->handle($request);
    if ($respuesta->getStatusCode() !== 200) { fwrite(STDERR, "$nombre: HTTP".$respuesta->getStatusCode()."\n"); exit(1); }
    $html = preg_replace(['/(<meta name="csrf-token" content=")[^"]+/', '/(name="_token" value=")[^"]+/'], ['$1FICTICIO','$1FICTICIO'], $respuesta->getContent());
    $html = preg_replace('#https?://[^/]+/build/#', '../../../../public/build/', $html);
    $html = preg_replace('#https?://[^/]+/img/#', '../../../../public/img/', $html);
    $html = preg_replace('/[ \t]+(?=\r?$)/m', '', $html);
    file_put_contents(base_path("docs/06-pruebas/B12-A23/capturas/$nombre.html"), $html);
    $kernel->terminate($request, $respuesta);
    echo "$nombre: HTTP200 real.\n";
}
