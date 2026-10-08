<?php
// Escenario ficticio para mirar A10 en el navegador. Solo corre en wings_testing_claude:
//   DB_DATABASE=wings_testing_claude A10_CLAVE=<clave descartable> php sembrar-navegador.php
use App\Models\{CashflowMovimiento, Rubro, Subrubro, TipoCaja, User};
use Illuminate\Support\Facades\{Artisan, DB, Hash};

$root = dirname(__DIR__, 5);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (DB::connection()->getDatabaseName() !== 'wings_testing_claude' || getenv('A10_CLAVE') === false) {
    fwrite(STDERR, "Solo en wings_testing_claude y con A10_CLAVE.\n");
    exit(1);
}
Artisan::call('migrate:fresh', ['--force' => true]);
$admin = User::factory()->create(['name' => 'Admin Ensayo', 'email' => 'admin.a10@ensayo.test', 'rol' => User::ROL_ADMIN, 'activo' => true, 'password' => Hash::make(getenv('A10_CLAVE'))]);
$alfa = TipoCaja::create(['nombre' => 'Caja Alfa', 'abreviatura' => 'ALF', 'activo' => true, 'saldo_inicial' => 777000]);
$beta = TipoCaja::create(['nombre' => 'Caja Beta', 'abreviatura' => 'BET', 'activo' => true, 'saldo_inicial' => 0]);
$sub = [];
foreach (['INGRESO', 'EGRESO'] as $tipo) {
    $rubro = Rubro::create(['nombre' => 'Rubro '.strtolower($tipo), 'tipo' => $tipo]);
    $sub[$tipo] = Subrubro::create(['rubro_id' => $rubro->id, 'nombre' => 'Sub '.strtolower($tipo), 'permitido_para' => User::ROL_ADMIN,
        'afecta_caja' => false, 'es_reservado_sistema' => false, 'activo' => true]);
}
$filas = [
    ['2024-01-31', 640, 'INGRESO', $alfa, 'Cobro'], ['2024-02-29', 1900, 'INGRESO', $alfa, 'Cobro'], ['2024-02-29', -350, 'EGRESO', $beta, 'Pago'],
    ['2024-03-01', 5000, 'INGRESO', $alfa, 'Cobro'],
    ['2026-09-27', 9100, 'INGRESO', $alfa, 'Domingo anterior'], ['2026-09-28', 730, 'INGRESO', $alfa, 'Lunes'], ['2026-10-04', -210, 'EGRESO', $beta, 'Domingo'],
    ['2026-10-05', 1500, 'INGRESO', $alfa, 'Lunes'], ['2026-10-08', 2600, 'INGRESO', $alfa, 'Cobro'], ['2026-10-08', -400, 'INGRESO', $alfa, 'Anulación de cobro'],
    ['2026-10-08', -900, 'EGRESO', $beta, 'Pago'], ['2026-10-08', 150, 'EGRESO', $beta, 'Reversión parcial de egreso'],
    ['2026-10-11', -330, 'EGRESO', $beta, 'Domingo'], ['2026-10-12', 8800, 'INGRESO', $alfa, 'Lunes siguiente'],
    ['2026-12-28', 1200, 'INGRESO', $alfa, 'Lunes'], ['2027-01-03', -200, 'EGRESO', $beta, 'Domingo'], ['2027-01-04', 7000, 'INGRESO', $alfa, 'Lunes siguiente'],
];
for ($i = 1; $i <= 34; $i++) {
    $filas[] = [sprintf('2026-06-%02d', ($i % 28) + 1), 10, 'INGRESO', $alfa, 'Relleno '.$i];
}
foreach ($filas as [$fecha, $monto, $tipo, $caja, $obs]) {
    CashflowMovimiento::create(['fecha' => $fecha, 'monto' => $monto, 'tipo_caja_id' => $caja->id, 'subrubro_id' => $sub[$tipo]->id,
        'usuario_admin_id' => $admin->id, 'observaciones' => $obs]);
}
echo 'Base '.DB::connection()->getDatabaseName().': '.CashflowMovimiento::count()." movimientos, usuario admin.a10@ensayo.test\n";
