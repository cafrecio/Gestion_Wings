<?php
declare(strict_types=1);

// Diagnóstico del renderer real del SITIO DE PRUEBA, sin ruta nueva ni escrituras.
require '/home/wingstest/app/vendor/autoload.php';
$app = require '/home/wingstest/app/bootstrap/app.php';
$request = Illuminate\Http\Request::create('https://test.gestionar-te.com.ar/__verificacion-t7-sin-contexto', 'GET');
$request->attributes->set('wings.error_sin_contexto', true);
$app->instance('request', $request);
// No se puede usar ninguna base del servidor: puerto deliberadamente cerrado.
$consultas = 0;
$app->beforeBootstrapping(Illuminate\Foundation\Bootstrap\RegisterProviders::class, function ($app): void {
    $app['config']->set(['database.default'=>'mysql', 'database.connections.mysql.database'=>'wings_testing_codex',
        'database.connections.mysql.host'=>'127.0.0.1', 'database.connections.mysql.port'=>1]);
});
$app->beforeBootstrapping(Illuminate\Foundation\Bootstrap\BootProviders::class, function () use (&$consultas): void {
    Illuminate\Support\Facades\DB::listen(function () use (&$consultas): void { $consultas++; });
});
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
$response = $app->make(Illuminate\Contracts\Debug\ExceptionHandler::class)
    ->render($request, new RuntimeException('Diagnóstico T7 del renderer sin contexto'));
if ($response->getStatusCode() !== 500 || $consultas !== 0
    || !str_contains($response->getContent(), 'Algo falló')) {
    file_put_contents('php://stderr', 'El renderer no devolvió el error esperado sin consultar una base.');
    exit(1);
}
file_put_contents('php://stderr', json_encode(['status'=>500,'consultas'=>0,'app'=>'/home/wingstest/app',
    'metodo'=>'renderer CLI; sin inducir HTTP 500 ni cambiar el servidor'], JSON_UNESCAPED_SLASHES)."\n");
echo $response->getContent();
