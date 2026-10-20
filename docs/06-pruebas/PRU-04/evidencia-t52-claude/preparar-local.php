<?php

// T52: clase vieja con marcas de alumnos que después cambiaron de grupo o se dieron de baja.
// Solo en la base descartable de Claude. Uso:
//   DB_DATABASE=wings_testing_claude php docs/06-pruebas/PRU-04/evidencia-t52-claude/preparar-local.php
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'wings_testing_claude') {
    throw new RuntimeException('Solo wings_testing_claude.');
}

use App\Models\{Alumno, Asistencia, Clase, Deporte, Grupo, Nivel, Profesor, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    $password = bin2hex(random_bytes(20));
    $operativa = User::factory()->create(['name' => 'Operativa prueba T52', 'email' => 'operativa-t52-'.time().'@example.test',
        'rol' => User::ROL_OPERATIVO, 'activo' => true, 'password' => bcrypt($password)]);
    $deporte = Deporte::create(['nombre' => 'Patín T52', 'tipo_liquidacion' => 'HORA', 'activo' => true]);
    $viejo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Principiantes T52'])->id, 'activo' => true]);
    $nuevo = Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => Nivel::create(['nombre' => 'Intermedias T52'])->id, 'activo' => true]);
    $profesora = Profesor::create(['deporte_id' => $deporte->id, 'nombre' => 'Lucía', 'apellido' => 'Méndez', 'dni' => '30111222',
        'fecha_nacimiento' => '1985-05-15', 'direccion' => 'Calle 1', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
    $dni = 52000001;
    $alumno = function ($apellido, $nombre) use (&$dni, $deporte, $viejo) {
        return Alumno::create(['nombre' => $nombre, 'apellido' => $apellido, 'dni' => (string) $dni++,
            'fecha_nacimiento' => '2012-01-01', 'celular' => '1111111111', 'deporte_id' => $deporte->id,
            'grupo_id' => $viejo->id, 'fecha_alta' => '2026-09-01', 'activo' => true]);
    };
    $cambio = $alumno('Cambio', 'Degrupo');
    $baja = $alumno('Baja', 'Deayer');
    $ausente = $alumno('Ausente', 'Marcada');
    $sigue = $alumno('Sigue', 'Enelgrupo');
    $clase = Clase::create(['grupo_id' => $viejo->id, 'fecha' => now()->subWeek()->toDateString(), 'hora_inicio' => '10:00',
        'hora_fin' => '11:00', 'cancelada' => false, 'validada_para_liquidacion' => false]);
    $clase->profesores()->attach($profesora->id);
    foreach ([[$cambio, true], [$baja, true], [$sigue, true], [$ausente, false]] as [$a, $presente]) {
        Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $a->id, 'presente' => $presente]);
    }
    $cambio->update(['grupo_id' => $nuevo->id]);
    $ausente->update(['grupo_id' => $nuevo->id]);
    $baja->update(['activo' => false]);
    file_put_contents(storage_path('app/t52-local.json'), json_encode([
        'email' => $operativa->email, 'password' => $password, 'clase' => $clase->id, 'ausente' => $ausente->id,
    ], JSON_THROW_ON_ERROR));
});
echo "Datos T52 creados en wings_testing_claude.\n";
