<?php
// Datos ficticios, base descartable. No guarda usuarios ni claves en la evidencia.
$base = dirname(__DIR__, 5);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->useStoragePath(getenv('LARAVEL_STORAGE_PATH'));
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{Alumno, AlumnoPlan, Asistencia, CajaOperativa, Clase, Configuracion, Deporte, Grupo, GrupoPlan, Liquidacion, MovimientoOperativo, Nivel, Profesor, Rubro, Subrubro, TipoCaja, User};
use Illuminate\Support\Facades\{Artisan, DB, Hash};
if (!$app->environment('testing') || DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Se requiere testing y wings_testing_codex.\n"); exit(1);
}
if (in_array($argv[1] ?? '', ['pendiente', 'terminada'], true)) {
    DB::table('primera_carga')->where('id', 1)->update(['estado' => strtoupper($argv[1])]);
    exit;
}
foreach (['migrate:fresh' => ['--force' => true], 'db:seed' => ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]] as $cmd => $args) {
    if (Artisan::call($cmd, $args) !== 0) throw new RuntimeException('Falló la preparación descartable');
}
DB::table('primera_carga')->where('id', 1)->update(['estado' => 'TERMINADA']);
$dep = Deporte::where('tipo_liquidacion', 'HORA')->firstOrFail();
$nivel = Nivel::firstOrFail();
$grupo = Grupo::create(['deporte_id' => $dep->id, 'nivel_id' => $nivel->id, 'activo' => true]);
$plan = GrupoPlan::create(['grupo_id' => $grupo->id, 'clases_por_semana' => 2, 'precio_mensual' => 30000, 'activo' => true]);
$prof = Profesor::create(['deporte_id' => $dep->id, 'nombre' => 'Profesor FICTICIO T15', 'apellido' => 'Prueba', 'dni' => '99101500', 'fecha_nacimiento' => '1990-01-01', 'direccion' => 'Ficticia', 'localidad' => 'Ficticia', 'telefono' => '1100000000', 'valor_hora' => 5000, 'activo' => true]);
app(App\Services\SubrubroSueldoService::class)->paraProfesor($prof);
$usuarios = [];
foreach (['ADMIN', 'OPERATIVO', 'PROFESOR'] as $rol) {
    $password = bin2hex(random_bytes(16));
    $u = User::factory()->create(['name' => $rol.' FICTICIO T15', 'email' => strtolower($rol).'@t15.ficticio.test', 'password' => Hash::make($password), 'rol' => $rol, 'activo' => true, 'profesor_id' => $rol === 'PROFESOR' ? $prof->id : null]);
    $usuarios[$rol] = ['id' => $u->id, 'email' => $u->email, 'password' => $password];
}
$tc = TipoCaja::where('activo', true)->firstOrFail();
$tc->update(['saldo_inicial' => 100000]);
app(App\Services\CajaService::class)->configurarMostrador($tc->id, $usuarios['ADMIN']['id']);
Configuracion::set('inscripcion_importe', '5000');
$alumno = Alumno::create(['nombre' => 'Alumno FICTICIO T15', 'apellido' => 'Prueba', 'dni' => '99101501', 'fecha_nacimiento' => '2000-01-01', 'fecha_alta' => today(), 'deporte_id' => $dep->id, 'grupo_id' => $grupo->id, 'celular' => '1100000000', 'activo' => true]);
AlumnoPlan::create(['alumno_id' => $alumno->id, 'plan_id' => $plan->id, 'fecha_desde' => today(), 'activo' => true]);
$clase = Clase::create(['grupo_id' => $grupo->id, 'fecha' => today(), 'hora_inicio' => '09:00', 'hora_fin' => '10:00', 'cancelada' => false, 'validada_para_liquidacion' => true]);
$clase->profesores()->attach($prof->id);
Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);
$caja = CajaOperativa::create(['usuario_operativo_id' => $usuarios['ADMIN']['id'], 'usuario_apertura_id' => $usuarios['ADMIN']['id'], 'apertura_at' => now(), 'estado' => 'ABIERTA', 'tipo_caja_efectivo_id' => $tc->id, 'efectivo_inicial' => 0, 'efectivo_heredado' => 0]);
$sub = Subrubro::where('permitido_para', 'OPERATIVO')->firstOrFail();
$mov = MovimientoOperativo::create(['caja_operativa_id' => $caja->id, 'fecha' => today(), 'tipo_caja_id' => $tc->id, 'subrubro_id' => $sub->id, 'monto' => 100, 'usuario_id' => $usuarios['ADMIN']['id'], 'estado' => 'ACTIVO', 'observaciones' => 'FICTICIO T15']);
$liq = Liquidacion::create(['profesor_id' => $prof->id, 'mes' => today()->month, 'anio' => today()->year, 'tipo' => 'HORA', 'total_calculado' => 5000, 'estado' => 'ABIERTA', 'estado_pago' => 'PENDIENTE']);
$ids = ['alumno' => $alumno->id, 'deporte' => $dep->id, 'grupo' => $grupo->id, 'nivel' => $nivel->id, 'profesor' => $prof->id, 'clase' => $clase->id, 'caja' => $caja->id, 'movimiento' => $mov->id, 'liquidacion' => $liq->id, 'tipo' => $tc->id, 'usuario' => $usuarios['OPERATIVO']['id'], 'rubro' => $sub->rubro_id, 'subrubro' => $sub->id];
$rutas = [];
foreach ($app['router']->getRoutes() as $route) {
    $uri = $route->uri();
    if (!in_array('GET', $route->methods(), true) || $route->isFallback || preg_match('#^(api|up|storage|recibos)/?|autocomplete|preview|check-|primera-carga/(plantilla|informe)#', $uri)) continue;
    $clave = match (true) {
        str_starts_with($uri, 'alumnos') => 'alumno', str_starts_with($uri, 'deportes') => 'deporte',
        str_starts_with($uri, 'grupos') => 'grupo', str_starts_with($uri, 'clases') => 'clase',
        str_starts_with($uri, 'niveles') => 'nivel', str_starts_with($uri, 'profesores') => 'profesor',
        str_starts_with($uri, 'tipos-caja') => 'tipo', str_starts_with($uri, 'usuarios') => 'usuario',
        str_starts_with($uri, 'liquidaciones') => 'liquidacion', str_contains($uri, 'subrubros') => 'subrubro',
        str_starts_with($uri, 'rubros') => 'rubro', default => 'caja',
    };
    $url = preg_replace_callback('/\{([^}]+)\}/', fn ($m) => (string)match ($m[1]) { 'alumnoId' => $ids['alumno'], 'cajaId' => $ids['caja'], 'movId' => $ids['movimiento'], 'rubroId' => $ids['rubro'], default => $ids[$clave] }, $uri);
    $rutas[] = ['nombre' => $route->getName() ?? $uri, 'url' => '/'.ltrim($url, '/')];
}
file_put_contents($base.'/storage/app/t15-fixture.json', json_encode(['database' => 'wings_testing_codex', 'usuarios' => $usuarios, 'rutas' => $rutas], JSON_THROW_ON_ERROR));
echo "Fixture ficticio preparado en wings_testing_codex; credenciales solo en storage ignorado.\n";
