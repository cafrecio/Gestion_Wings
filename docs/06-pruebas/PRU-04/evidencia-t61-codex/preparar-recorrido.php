<?php

// Solo datos ficticios para el recorrido de Chrome de T61. No carga los dos traspasos:
// los debe registrar la persona que verifica desde los formularios de Wings.
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('T61: solo CLI, APP_ENV=testing y DB_DATABASE=wings_testing_codex.');
}
$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex' || !app()->environment('testing')) {
    throw new RuntimeException('La configuración efectiva no apunta a la base descartable propia.');
}
if (App\Models\User::exists() || App\Models\CashflowMovimiento::exists() || App\Models\CajaOperativa::exists()) {
    throw new RuntimeException('El recorrido requiere una base descartable recién migrada. No se borra nada aquí.');
}
Illuminate\Support\Facades\Notification::fake();
(new Database\Seeders\CatalogosSeeder)->run();
Illuminate\Support\Facades\DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
$admin = App\Models\User::factory()->create(['name' => 'Dueño prueba T61', 'email' => 'admin.t61@wings.test',
    'password' => Illuminate\Support\Facades\Hash::make('PruebaLocalT61!'), 'rol' => 'ADMIN', 'activo' => true]);
$op = App\Models\User::factory()->create(['name' => 'Mostrador prueba T61', 'email' => 'operativa.t61@wings.test',
    'password' => Illuminate\Support\Facades\Hash::make('PruebaLocalT61!'), 'rol' => 'OPERATIVO', 'activo' => true]);
$efectivo = App\Models\TipoCaja::where('nombre', 'Efectivo')->firstOrFail();
$banco = App\Models\TipoCaja::where('nombre', 'Banco Nación')->firstOrFail();
$efectivo->update(['saldo_inicial' => '600000.00']);
app(App\Services\CajaService::class)->configurarMostrador($efectivo->id, $admin->id);
Tests\Support\CajaDeclarada::crear($op->id, $efectivo->id);
$servicio = app(App\Services\CashflowService::class);
foreach ([['Intereses Banco', $banco, '12000.00'], ['Luz', $banco, '5000.00'],
    ['Patines', $efectivo, '8000.00'], ['Retiro de dueños', $efectivo, '2000.00']] as [$nombre, $medio, $monto]) {
    $servicio->registrarMovimientoAdmin(['usuario_admin_id' => $admin->id, 'subrubro_id' => App\Models\Subrubro::where('nombre', $nombre)->value('id'),
        'tipo_caja_id' => $medio->id, 'monto' => $monto, 'fecha' => today()->toDateString(), 'observaciones' => 'Base ficticia T61: '.$nombre]);
}
$reporte = app(App\Services\ReporteMensualService::class)->obtener(today()->format('Y-m'));
echo json_encode(['base' => config('database.connections.mysql.database'), 'fecha' => today()->toDateString(),
    'datos' => 'Ficticios; traspasos todavía sin cargar', 'ingresos' => $reporte['ingresos'], 'egresos' => $reporte['egresos'],
    'resultado' => $reporte['resultado'], 'aportes' => $reporte['aportes'], 'retiros' => $reporte['retiros'],
    'cajas' => $reporte['cajas']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
