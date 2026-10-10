<?php

// Auxiliar propio: la prueba coordinadora libera al primero cuando ve al segundo esperando.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::create('/'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$basePrueba = Illuminate\Support\Facades\DB::connection()->getDatabaseName();
if (!in_array($basePrueba, ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
    throw new RuntimeException('Usar la base descartable propia del agente.');
}
[$archivo, $configPath, $turno] = $argv;
$config = json_decode(file_get_contents($configPath), true, flags: JSON_THROW_ON_ERROR);
$pedidoConfig = $config[$turno];
$barrera = dirname($configPath);
$conexion = Illuminate\Support\Facades\DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
Illuminate\Support\Facades\Auth::guard('web')->setUser(App\Models\User::findOrFail($pedidoConfig['admin_id']));

if ($turno === 'primero') {
    $detenido = false;
    Illuminate\Support\Facades\DB::listen(function ($consulta) use ($barrera, $conexion, &$detenido): void {
        $sql = strtolower($consulta->sql);
        if (!$detenido && str_starts_with($sql, 'select') && str_contains($sql, 'from `rubros`')
            && str_contains($sql, 'for update')) {
            $detenido = true;
            file_put_contents($barrera.'/primero-con-candado.json', json_encode([
                'conexion' => $conexion, 'transaccion' => Illuminate\Support\Facades\DB::transactionLevel(),
                'consulta' => $consulta->sql, 'parametros' => $consulta->bindings], JSON_THROW_ON_ERROR));
            $limite = microtime(true) + 25;
            while (!is_file($barrera.'/liberar-primero')) {
                if (microtime(true) > $limite) throw new RuntimeException('La prueba no liberó el primer pedido.');
                usleep(10000);
            }
        }
    });
}
file_put_contents($barrera.'/'.$turno.'-iniciado.json', json_encode(['conexion' => $conexion], JSON_THROW_ON_ERROR));
$inicio = microtime(true);
$pedido = Illuminate\Http\Request::create($pedidoConfig['ruta'], $pedidoConfig['metodo'], $pedidoConfig['datos']);
$respuesta = $kernel->handle($pedido);
$kernel->terminate($pedido, $respuesta);
$session = $pedido->hasSession() ? $pedido->session() : null;
$resultado = json_encode(['turno' => $turno, 'operacion' => $pedidoConfig['operacion'],
    'metodo' => $pedidoConfig['metodo'], 'ruta' => $pedidoConfig['ruta'], 'conexion' => $conexion,
    'status' => $respuesta->getStatusCode(), 'success' => $session?->get('success'),
    'error' => $session?->get('error'), 'validaciones' => $session?->has('errors')
        ? $session->get('errors')->getBag('default')->messages() : [],
    'transaccion_al_terminar' => Illuminate\Support\Facades\DB::transactionLevel(),
    'segundos' => round(microtime(true) - $inicio, 3)],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents($barrera.'/'.$turno.'-resultado.json', $resultado);
echo $resultado;
