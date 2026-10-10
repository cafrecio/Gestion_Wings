<?php

// Auxiliar de la verificación: dos pedidos HTTP reales, en la base descartable propia.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->instance('request', Illuminate\Http\Request::create('/'));
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

if (!in_array(Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
    ['wings_testing_codex', 'wings_testing_claude', 'wings_testing_gemini'], true)) {
    throw new RuntimeException('Esta verificación exige la base descartable propia del agente.');
}
[$archivo, $modo, $adminId, $rubroId, $subrubroId, $barrera] = $argv;
Illuminate\Support\Facades\Auth::guard('web')->setUser(App\Models\User::findOrFail($adminId));
$esperar = static function (string $ruta): void {
    $limite = microtime(true) + 20;
    while (!is_file($ruta)) {
        if (microtime(true) > $limite) throw new RuntimeException('Barrera de prueba agotada: '.$ruta);
        usleep(10000);
    }
};

if ($modo === 'padre') {
    $intercalado = false;
    Illuminate\Support\Facades\DB::listen(function ($consulta) use ($barrera, $esperar, &$intercalado): void {
        if (!$intercalado && str_starts_with(strtolower($consulta->sql), 'select')
            && str_contains($consulta->sql, '`subrubros`')
            && str_contains($consulta->sql, '`clasificacion_resultado` not in')) {
            $intercalado = true;
            file_put_contents($barrera.'/padre-validado', $consulta->sql);
            $esperar($barrera.'/hijo-guardado');
        }
    });
    $ruta = '/rubros/'.$rubroId;
    $datos = ['nombre' => 'Concurrencia T16', 'tipo' => 'EGRESO'];
} else {
    $esperar($barrera.'/padre-validado');
    $ruta = '/rubros/'.$rubroId.'/subrubros/'.$subrubroId;
    $datos = ['nombre' => 'Aporte concurrente T16', 'permitido_para' => 'ADMIN',
        'afecta_caja' => false, 'clasificacion_resultado' => 'APORTE'];
}

$pedido = Illuminate\Http\Request::create($ruta, 'PUT', $datos);
$respuesta = $kernel->handle($pedido);
$kernel->terminate($pedido, $respuesta);
$resultado = ['pedido' => $modo, 'ruta' => $ruta, 'status' => $respuesta->getStatusCode(),
    'error' => $pedido->hasSession() ? $pedido->session()->get('error') : null,
    'validaciones' => $pedido->hasSession() && $pedido->session()->has('errors')
        ? $pedido->session()->get('errors')->all() : [],
    'tipo_guardado' => App\Models\Rubro::findOrFail($rubroId)->tipo,
    'clasificacion_guardada' => App\Models\Subrubro::findOrFail($subrubroId)->clasificacion_resultado];
if ($modo === 'hijo') file_put_contents($barrera.'/hijo-guardado', json_encode($resultado));
echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
