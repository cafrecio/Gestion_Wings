<?php

// T59 y T47: dueño y operativa sintéticos, solo en la base descartable de Claude.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t59-t47-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{CajaOperativa, TipoCaja, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $datos = [];
    foreach (['dueno' => User::ROL_ADMIN, 'operativa' => User::ROL_OPERATIVO] as $clave => $rol) {
        $password = bin2hex(random_bytes(20));
        $u = User::factory()->create(['name' => ucfirst($clave).' prueba T59-T47', 'email' => "$clave-t59t47-".time().'@example.test',
            'rol' => $rol, 'activo' => true, 'password' => bcrypt($password)]);
        $datos[$clave] = ['email' => $u->email, 'password' => $password, 'id' => $u->id];
    }
    // Turno abierto de la operativa: el que no tiene que recibir nada del dueño.
    $caja = CajaOperativa::create(['usuario_operativo_id' => $datos['operativa']['id'], 'usuario_apertura_id' => $datos['operativa']['id'],
        'apertura_at' => now(), 'estado' => 'ABIERTA', 'tipo_caja_efectivo_id' => TipoCaja::firstOrFail()->id, 'efectivo_inicial' => 0]);
    $datos['caja'] = $caja->id;
    file_put_contents(storage_path('app/t59t47-local.json'), json_encode($datos, JSON_THROW_ON_ERROR));
});
echo "Datos T59/T47 creados en wings_testing_claude.\n";
