<?php

// Solo datos sintéticos, en la base descartable propia. Ejecutar antes del navegador,
// sin una suite simultánea: la suite posterior rehace la base.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo wings_testing_codex.');
}
Illuminate\Support\Facades\DB::transaction(function () {
    Illuminate\Support\Facades\DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $admin = App\Models\User::factory()->create([
        'name' => 'Dueño prueba T37', 'email' => 'dueno-t37-'.time().'@example.test',
        'rol' => App\Models\User::ROL_ADMIN, 'activo' => true, 'password' => bcrypt($password),
    ]);
    $operativa = App\Models\User::factory()->create([
        'name' => 'Operativa prueba T37', 'rol' => App\Models\User::ROL_OPERATIVO, 'activo' => true,
    ]);
    $medio = App\Models\TipoCaja::create(['nombre' => 'Billetes T37', 'abreviatura' => 'BIL', 'activo' => true]);
    $ids = [];
    foreach (range(1, 4) as $n) {
        $caja = App\Models\CajaOperativa::create([
            'usuario_operativo_id' => $operativa->id, 'estado' => 'CERRADA',
            'apertura_at' => now()->subHours(2), 'cierre_at' => now(),
            'tipo_caja_efectivo_id' => $medio->id, 'efectivo_inicial' => 10000,
            'efectivo_esperado' => 10000, 'efectivo_contado' => 10000,
            'cambio_retenido' => 10000, 'efectivo_retirado' => 0, 'diferencia_efectivo' => 0,
        ]);
        $ids[] = $caja->id;
    }
    // Credenciales efímeras fuera de la evidencia versionada, nunca imprimirlas.
    file_put_contents(storage_path('app/t37-local.json'), json_encode([
        'email' => $admin->email, 'password' => $password, 'cajas' => $ids,
    ], JSON_THROW_ON_ERROR));
});
echo "Fixture T37 creado en wings_testing_codex.\n";
