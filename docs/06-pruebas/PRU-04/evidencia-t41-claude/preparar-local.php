<?php

// T41: liquidaciones de septiembre y octubre para mirar la ficha en celular, solo en la base descartable.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t41-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{Clase, Deporte, Grupo, Nivel, Profesor, User};
use App\Services\LiquidacionService;
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $dueno = User::factory()->create(['name' => 'Dueño prueba T41', 'email' => 'dueno-t41-'.time().'@example.test',
        'rol' => User::ROL_ADMIN, 'activo' => true, 'password' => bcrypt($password)]);
    $deporte = Deporte::create(['nombre' => 'Patín T41', 'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'activo' => true]);
    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Inicial T41'])->id, 'activo' => true]);
    $profesor = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Laura', 'apellido' => 'Gómez', 'dni' => '25444333',
        'fecha_nacimiento' => '1988-03-12', 'direccion' => 'Calle 10', 'localidad' => 'Rosario',
        'tipo_liquidacion' => Deporte::TIPO_LIQUIDACION_HORA, 'valor_hora' => 10000, 'activo' => true]);
    $ids = [];
    foreach (['2026-09-15' => [9, 2026], '2026-10-15' => [10, 2026]] as $fecha => [$mes, $anio]) {
        $clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => '10:00', 'hora_fin' => '11:00',
            'validada_para_liquidacion' => true, 'cancelada' => false]);
        $clase->profesores()->attach($profesor->id);
        $ids[$mes] = app(LiquidacionService::class)->generarLiquidacionMensual($profesor->id, $mes, $anio)->id;
    }
    file_put_contents(storage_path('app/t41-local.json'), json_encode(['email' => $dueno->email, 'password' => $password,
        'septiembre' => $ids[9], 'octubre' => $ids[10]], JSON_THROW_ON_ERROR));
});
echo "Datos T41 creados en wings_testing_claude.\n";
