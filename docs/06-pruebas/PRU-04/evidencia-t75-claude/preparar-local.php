<?php

// T75: dos revisiones pendientes para probar el interruptor de Revisión, solo en la base descartable.
// Uso: DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t75-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{Alumno, AlumnoPlan, AlumnoRevisionCobranza, Deporte, Grupo, GrupoPlan, Nivel, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $dueno = User::factory()->create(['name' => 'Dueño prueba T75', 'email' => 'dueno-t75-'.time().'@example.test',
        'rol' => User::ROL_ADMIN, 'activo' => true, 'password' => bcrypt($password)]);
    $deporte = Deporte::create(['nombre' => 'Patín T75', 'tipo_liquidacion' => 'COMISION', 'activo' => true]);
    $grupo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Nivel T75'])->id, 'activo' => true]);
    $plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 10000, 'activo' => true]);
    $ids = [];
    foreach ([['Bruno', '75000001'], ['Carla', '75000002']] as [$nombre, $dni]) {
        $alumno = Alumno::create(['nombre' => $nombre, 'apellido' => 'Revision', 'dni' => $dni,
            'fecha_nacimiento' => '2012-01-01', 'celular' => '11-4000-0000', 'fecha_alta' => '2026-03-01',
            'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
        AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-03-01', 'activo' => true]);
        $revision = AlumnoRevisionCobranza::create(['alumno_id' => $alumno->id, 'periodo_objetivo' => now()->format('Y-m'),
            'motivo' => 'SIN_ASISTENCIAS_NI_ALTA_PAGO_RECIENTES', 'estado_revision' => AlumnoRevisionCobranza::ESTADO_PENDIENTE]);
        $ids[$nombre] = ['alumno' => $alumno->id, 'revision' => $revision->id];
    }
    file_put_contents(storage_path('app/t75-local.json'), json_encode(['email' => $dueno->email, 'password' => $password, 'ids' => $ids], JSON_THROW_ON_ERROR));
});
echo "Datos T75 creados en wings_testing_claude.\n";
