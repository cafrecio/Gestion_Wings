<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex'
    || \App\Models\PrimeraCarga::pendiente()) {
    fwrite(STDERR, "Solo escenario ficticio completo en wings_testing_codex.\n");
    exit(1);
}
config(['session.driver'=>'array','session.connection'=>null,'cache.default'=>'array']);
set_exception_handler(static function (\Throwable $error): void {
    fwrite(STDERR, "Captura fallida: ".$error->getMessage()."\n");
    exit(1);
});
\Carbon\Carbon::setTestNow('2026-10-09 18:00:00');
$admin = \App\Models\User::where('rol','ADMIN')->firstOrFail();
\Illuminate\Support\Facades\Auth::setUser($admin);
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$base = base_path('docs/06-pruebas/B12-A23/menu');
if (!is_dir($base)) mkdir($base,0775,true);
$modelos = ['alumnos'=>\App\Models\Alumno::class,'clases'=>\App\Models\Clase::class,
    'deportes'=>\App\Models\Deporte::class,'niveles'=>\App\Models\Nivel::class,
    'grupos'=>\App\Models\Grupo::class,'profesores'=>\App\Models\Profesor::class,
    'liquidaciones'=>\App\Models\Liquidacion::class,'rubros'=>\App\Models\Rubro::class,
    'subrubros'=>\App\Models\Subrubro::class,'tipos-caja'=>\App\Models\TipoCaja::class,
    'usuarios'=>\App\Models\User::class,'caja'=>\App\Models\CajaOperativa::class,
    'cajas'=>\App\Models\CajaOperativa::class];
$inventario = [];
foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
    $nombre = $route->getName();
    $uri = $route->uri();
    if (!in_array('GET',$route->methods(),true) || !$nombre
        || !in_array('auth',$route->gatherMiddleware(),true)
        || preg_match('#(?:check-|autocomplete|preview|recibos/|/plantilla|/informe)#',$uri)) continue;
    $segmento = explode('/',$uri)[0];
    $ruta = '/'.$uri;
    if ($route->parameterNames()) {
        $modelo = $modelos[$segmento] ?? null;
        if (!$modelo) throw new RuntimeException('Sin modelo de captura: '.$nombre);
        foreach ($route->parameterNames() as $parametro) {
            $id = $parametro === 'alumnoId' ? \App\Models\Alumno::value('id')
                : ($parametro === 'movId' ? \App\Models\MovimientoOperativo::value('id') : $modelo::value('id'));
            if (!$id) throw new RuntimeException('Sin registro ficticio: '.$nombre);
            $ruta = str_replace('{'.$parametro.'}',(string)$id,$ruta);
        }
    }
    $request = \Illuminate\Http\Request::create($ruta);
    $response = $kernel->handle($request);
    $html = $response->getContent();
    $fila = ['nombre'=>$nombre,'ruta'=>$ruta,'http'=>$response->getStatusCode(),
        'capturada'=>false,'destino'=>$response->headers->get('Location')];
    if ($response->getStatusCode() === 200 && str_contains($html,'class="ds-sidebar"')) {
        if (!preg_match('/<a href="[^"]*\/reportes"\s+class="ds-nav-link[^\"]*"[^>]*>\s*<svg.*?Reportes\s*<\/a>/s',$html)) {
            throw new RuntimeException('Falta acceso Reportes: '.$nombre);
        }
        $html = preg_replace(['/(<meta name="csrf-token" content=")[^"]+/','/(name="[^"]*_token" value=")[^"]+/'],['$1FICTICIO','$1FICTICIO'],$html);
        $html = preg_replace('#https?://[^/]+/(build|img)/#','../../../../public/$1/',$html);
        $html = preg_replace('/[ \t]+(?=\r?$)/m','',$html);
        $archivo = str_replace('.','-',$nombre);
        if (file_put_contents($base.'/'.$archivo.'.html',$html) === false) throw new RuntimeException('No se guardó '.$archivo);
        $fila['capturada'] = true;
        $fila['archivo'] = $archivo;
    }
    $inventario[] = $fila;
    $kernel->terminate($request,$response);
    echo $nombre.': HTTP'.$fila['http'].($fila['capturada'] ? ' pantalla real' : ' sin pantalla/redirect')."\n";
}
// Comprobar permisos y menú sin crear cuentas ni exportar users.
$roles = [];
foreach (['OPERATIVO'=>'/operativo','PROFESOR'=>'/clases'] as $rol=>$inicio) {
    $user = \App\Models\User::where('rol',$rol)->first();
    if (!$user) {
        $user = (new \App\Models\User())->forceFill(['name'=>'Rol de prueba','rol'=>$rol,'activo'=>true]);
        $user->id = -1;
    }
    \Illuminate\Support\Facades\Auth::setUser($user);
    $r = $kernel->handle(\Illuminate\Http\Request::create($inicio));
    if ($r->getStatusCode() !== 200 || preg_match('#<a[^>]+href="[^"]*/reportes#',$r->getContent())) throw new RuntimeException('Menú de rol incorrecto: '.$rol);
    foreach (['/reportes','/reportes/alumnos','/reportes/sueldos'] as $ruta) {
        $r = $kernel->handle(\Illuminate\Http\Request::create($ruta));
        if ($r->getStatusCode() !== 403) throw new RuntimeException('Acceso inesperado: '.$rol.' '.$ruta);
    }
    $roles[$rol] = 'sin entrada; tres reportes403';
}
file_put_contents($base.'/inventario.json',json_encode(['pantallas'=>$inventario,'roles'=>$roles],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
echo 'Pantallas: '.count(array_filter($inventario,fn ($f)=>$f['capturada']))."; roles comprobados.\n";
