<?php
// Verificación T85 (Claude): base descartable wings_testing_claude, datos mínimos.
$base = 'C:/xampp/htdocs/gestion-wings';
putenv('DB_DATABASE=wings_testing_claude'); $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = 'wings_testing_claude';
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB, Artisan};
if (DB::connection()->getDatabaseName() !== 'wings_testing_claude') { fwrite(STDERR, "base inesperada\n"); exit(2); }
Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => 'Database\Seeders\CatalogosSeeder', '--force' => true]);
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
App\Models\User::create(['name' => 'Dueño T85', 'email' => 'duenio-t85@wings.test', 'password' => bcrypt('ClaveT85-verifica'), 'rol' => 'ADMIN', 'activo' => true]);
$dep = App\Models\Deporte::firstOrCreate(['nombre' => 'Patín T85'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$g = App\Models\Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => App\Models\Nivel::firstOrCreate(['nombre' => 'Avanzadas'])->id, 'activo' => true]);
$p = App\Models\GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 2, 'precio_mensual' => 35000, 'activo' => true]);
$a = App\Models\Alumno::create(['nombre' => 'Agustín', 'apellido' => 'Acosta', 'dni' => '44400005', 'fecha_nacimiento' => '2012-01-01', 'celular' => '1155550000',
    'deporte_id' => $dep->id, 'grupo_id' => $g->id, 'fecha_alta' => '2024-03-10', 'activo' => true]);
App\Models\AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $p->id, 'fecha_desde' => '2024-03-10', 'activo' => true]);
echo json_encode(['alumno' => $a->id, 'cuotas' => App\Models\DeudaCuota::where('alumno_id', $a->id)->count()])."\n";
