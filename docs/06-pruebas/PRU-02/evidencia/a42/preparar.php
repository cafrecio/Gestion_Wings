<?php
// Fixture descartable de A42. No usa ni exporta datos del club.
$base = dirname(__DIR__, 5);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{Alumno, AlumnoPlan, Configuracion, Deporte, Grupo, GrupoPlan, Nivel, Pago, User};
use Illuminate\Support\Facades\{Artisan, DB, Hash};
if (!$app->environment('testing') || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Solo APP_ENV=testing y wings_testing_codex. No se hizo ninguna escritura.\n");
    exit(1);
}
if (in_array('--comprobar', $argv, true)) {
    $fixture = json_decode(file_get_contents($base.'/storage/app/a42-fixture.json'), true, flags: JSON_THROW_ON_ERROR);
    $rows = [];
    foreach ($fixture['alumnos'] as $case => $expected) {
        $alumno = Alumno::findOrFail($expected['id']);
        $cargo = app(App\Services\InscripcionService::class)->cargo($alumno->dni);
        $row = ['id' => $alumno->id, 'dni' => $alumno->dni, 'fecha_alta' => $alumno->fecha_alta->format('Y-m-d'), 'saldo' => $cargo?->saldo_pendiente];
        if ($row != $expected) { fwrite(STDERR, "El escenario $case fue modificado.\n"); exit(2); }
        $rows[$case] = $row;
    }
    if (Alumno::where('dni', '99042004')->exists()) { fwrite(STDERR, "El alta inválida fue guardada.\n"); exit(3); }
    file_put_contents(__DIR__.'/resultado-datos.json', json_encode(['database' => DB::connection()->getDatabaseName(), 'filas' => $rows, 'alta_invalida_guardada' => false], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n");
    echo "Tres filas ficticias y saldos conservados; alta inválida no guardada.\n";
    exit(0);
}
$exit = Artisan::call('migrate:fresh', ['--force' => true]);
if ($exit !== 0) { fwrite(STDERR, Artisan::output()); exit($exit); }
$exit = Artisan::call('db:seed', ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]);
if ($exit !== 0) { fwrite(STDERR, Artisan::output()); exit($exit); }
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
Configuracion::set('inscripcion_importe', '5000');
$password = bin2hex(random_bytes(16));
$user = User::factory()->create(['name' => 'Verificador FICTICIO A42', 'email' => 'a42@ficticio.test', 'password' => Hash::make($password), 'rol' => 'ADMIN', 'activo' => true]);
$grupo = Grupo::create(['deporte_id' => Deporte::firstOrFail()->id, 'nivel_id' => Nivel::firstOrFail()->id, 'activo' => true]);
$plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
$hoy = today()->format('Y-m-d');
$anterior = today()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d');
$alumnos = [];
foreach (['pendiente', 'pagada', 'sin_cargo'] as $i => $caso) {
    $alumno = Alumno::create(['nombre' => 'FICTICIO', 'apellido' => 'A42 '.$caso, 'dni' => (string)(99042001+$i), 'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => $hoy, 'deporte_id' => $grupo->deporte_id, 'grupo_id' => $grupo->id, 'activo' => true]);
    AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => $hoy, 'activo' => true]);
    if ($caso !== 'sin_cargo') app(App\Services\InscripcionService::class)->sincronizar($alumno, $user->id);
    if ($caso === 'pagada') {
        $pago = Pago::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'mes' => today()->month, 'anio' => today()->year, 'monto_base' => 5000, 'porcentaje_aplicado' => 100, 'monto_final' => 5000, 'monto_cuota' => 0, 'fecha_pago' => $hoy, 'estado' => Pago::ESTADO_COMPLETADO, 'observaciones' => 'Fixture FICTICIO A42; solo aviso, no flujo de caja.']);
        app(App\Services\InscripcionService::class)->cargo($alumno->dni)->pagos()->attach($pago->id, ['monto_aplicado' => 5000]);
    }
    $cargo = app(App\Services\InscripcionService::class)->cargo($alumno->dni);
    $alumnos[$caso] = ['id' => $alumno->id, 'dni' => $alumno->dni, 'fecha_alta' => $hoy, 'saldo' => $cargo?->saldo_pendiente];
}
$fixture = ['database' => DB::connection()->getDatabaseName(), 'email' => $user->email, 'password' => $password, 'alumnos' => $alumnos, 'grupo_id' => $grupo->id, 'deporte_id' => $grupo->deporte_id, 'plan_id' => $plan->id, 'hoy' => $hoy, 'anterior' => $anterior, 'periodo' => today()->format('Y-m')];
file_put_contents($base.'/storage/app/a42-fixture.json', json_encode($fixture, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "Fixture FICTICIO guardado solo en storage/app: ".json_encode($alumnos, JSON_UNESCAPED_UNICODE)."\n";