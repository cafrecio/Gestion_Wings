<?php

require __DIR__.'/verificar-entorno.php';

use App\Models\{Alumno, AlumnoPlan, Deporte, Grupo, GrupoPlan, Nivel, PrimeraCarga, User};
use App\Support\FechaIngresoRegla;
use Illuminate\Support\Facades\Hash;

if (User::exists() || Alumno::exists()) {
    throw new RuntimeException('Se requiere la base propia vacía de usuarios/alumnos; este script no borra datos.');
}
(new Database\Seeders\CatalogosSeeder)->run();
PrimeraCarga::whereKey(1)->update(['estado' => 'TERMINADA', 'detalle' => null]);
$admin = User::factory()->create(['name' => 'Control local T50', 'email' => 'admin.t50@wings.test',
    'password' => Hash::make('SoloPruebaT50!'), 'rol' => 'ADMIN', 'activo' => true]);
$grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id, 'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
$plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
$historico = Alumno::create(['dni' => '99005999', 'nombre' => 'Registro', 'apellido' => 'Historico ficticio T50',
    'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000050', 'email' => 'historico.t50@wings.test',
    'fecha_alta' => '2027-05-10', 'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id]);
AlumnoPlan::create(['alumno_id' => $historico->id, 'plan_id' => $plan->id, 'fecha_desde' => '2027-05-10', 'activo' => true]);
$datos = ['base' => config('database.connections.mysql.database'), 'usuario' => $admin->email,
    'fecha_servidor' => now()->toDateTimeString(), 'limite_servidor' => FechaIngresoRegla::limiteMaximo()->toDateString(),
    'grupo' => $grupo->id, 'plan' => $plan->id, 'alumno_historico_ficticio' => $historico->id,
    'nota' => 'Datos ficticios; ningún alta de prueba nueva fue cargada todavía por navegador.'];
file_put_contents(__DIR__.'/fixture-navegador.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);
echo json_encode($datos, JSON_UNESCAPED_UNICODE).PHP_EOL;
