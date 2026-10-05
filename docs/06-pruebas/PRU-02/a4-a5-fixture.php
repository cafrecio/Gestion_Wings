<?php

// Datos sintéticos, exclusivamente para recorrer A4/A5 en el navegador local.
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex') {
    throw new RuntimeException('Solo CLI / testing / wings_testing_codex.');
}
require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('testing') || config('database.connections.mysql.database') !== 'wings_testing_codex') {
    throw new RuntimeException('No se permite otra base.');
}
use App\Models\{Alumno, AlumnoPlan, Clase, Deporte, Grupo, GrupoPlan, Nivel, Profesor, User};
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
    User::firstOrCreate(['email' => 'a4a5@example.invalid'], ['name' => 'Prueba A4/A5', 'password' => bcrypt('Prueba-local-A4-A5!')])->forceFill(['rol' => 'ADMIN', 'activo' => true])->save();
    $nivel = Nivel::firstOrCreate(['nombre' => 'Inicial A4/A5']);
    foreach (['Patín A4/A5', 'Fútbol A4/A5'] as $i => $nombre) {
        $deporte = Deporte::firstOrCreate(['nombre' => $nombre], ['activo' => true, 'tipo_liquidacion' => 'HORA']);
        $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id], ['activo' => true]);
        $plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id, 'clases_por_semana' => 2], ['precio_mensual' => 30000, 'activo' => true]);
        $profesor = Profesor::firstOrCreate(['dni' => '4600090'.$i], ['nombre' => $i ? 'Luis' : 'Ana', 'apellido' => 'Prueba', 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'deporte_id' => $deporte->id, 'valor_hora' => 1000, 'activo' => true]);
        if ($i === 0) {
            Profesor::firstOrCreate(['dni' => '46000909'], ['nombre' => 'Inactivo', 'apellido' => 'Prueba', 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'deporte_id' => $deporte->id, 'valor_hora' => 1000, 'activo' => false]);
            $alumno = Alumno::firstOrCreate(['dni' => '47000901', 'deporte_id' => $deporte->id], ['nombre' => 'Alumno', 'apellido' => 'Prueba', 'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => today(), 'celular' => '1111111111', 'grupo_id' => $grupo->id, 'activo' => true]);
            AlumnoPlan::firstOrCreate(['alumno_id' => $alumno->id, 'plan_id' => $plan->id], ['fecha_desde' => today(), 'activo' => true]);
            $clase = Clase::firstOrCreate(['grupo_id' => $grupo->id, 'fecha' => today()->addDay()->format('Y-m-d'), 'hora_inicio' => '17:00'], ['hora_fin' => '18:00', 'cancelada' => false]);
            $clase->profesores()->syncWithoutDetaching([$profesor->id]);
            echo 'Alumno: '.$alumno->id.'; clase: '.$clase->id.'; grupo Patín: '.$grupo->id.PHP_EOL;
        }
    }
});
