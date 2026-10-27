<?php

/** Preparación sintética exclusiva de la base descartable local de Codex. */
require __DIR__.'/../evidencia-verificacion-t92-codex/bootstrap.php';
$raiz = dirname(__DIR__, 4);
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') throw new RuntimeException('Base efectiva distinta.');
Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]);
Illuminate\Support\Facades\Notification::fake();
App\Models\PrimeraCarga::whereKey(1)->update(['estado' => 'TERMINADA']);
$admin = App\Models\User::factory()->create(['name' => 'Dueño de prueba B3', 'email' => 'admin-b3@example.test', 'rol' => 'ADMIN', 'activo' => true]);
$op = App\Models\User::factory()->create(['name' => 'Operativa de prueba B3', 'email' => 'operativa-b3@example.test', 'rol' => 'OPERATIVO', 'activo' => true]);
$deporte = App\Models\Deporte::firstOrFail();
$g = App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => App\Models\Nivel::firstOrFail()->id, 'activo' => true]);
$plan = App\Models\GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
$prof = App\Models\Profesor::create(['nombre' => 'Profesora', 'apellido' => 'Prueba B3', 'dni' => '30999123',
    'fecha_nacimiento' => '1980-01-01', 'deporte_id' => $deporte->id, 'direccion' => 'Prueba', 'localidad' => 'Prueba', 'valor_hora' => 1000, 'activo' => true]);
$userProf = App\Models\User::factory()->create(['name' => 'Profesora de prueba B3', 'email' => 'profesora-b3@example.test', 'rol' => 'PROFESOR', 'profesor_id' => $prof->id, 'activo' => true]);
$efectivo = App\Models\TipoCaja::firstOrFail();
app(App\Services\CajaService::class)->configurarMostrador($efectivo->id, $admin->id);
$clase = App\Models\Clase::create(['grupo_id' => $g->id, 'fecha' => today()->toDateString(), 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
$clase->profesores()->attach($prof->id);
$alumnos = [];
foreach (['Nuevo', 'Deudor', 'Moroso', 'En plazo', 'Al día', 'Margen'] as $indice => $nombre) {
    $alta = in_array($nombre, ['En plazo', 'Margen']) ? today()->subDay()->toDateString() : '2026-01-01';
    $a = App\Models\Alumno::create(['nombre' => $nombre, 'apellido' => 'Prueba B3', 'dni' => (string) (33888100 + $indice),
        'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'fecha_alta' => $alta,
        'deporte_id' => $g->deporte_id, 'grupo_id' => $g->id, 'activo' => true]);
    App\Models\AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'fecha_desde' => $alta, 'activo' => true]);
    if (!in_array($nombre, ['Nuevo', 'Margen'])) {
        $periodo = in_array($nombre, ['En plazo', 'Al día']) ? today()->format('Y-m') : '2026-01';
        App\Models\DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
        app(App\Services\PagoCuotaService::class)->registrarPagoCuotaAdmin(['alumno_id' => $a->id,
            'usuario_admin_id' => $admin->id, 'tipo_caja_id' => $efectivo->id,
            'items' => [['periodo' => $periodo, 'monto' => $nombre === 'En plazo' ? 100 : 28000]],
            'fecha_pago' => $nombre === 'En plazo' ? $alta : $periodo.'-01',
            'motivo' => 'Pago inicial parcial acordado para el escenario En plazo.']);
    }
    if (in_array($nombre, ['Deudor', 'Moroso', 'Margen'])) {
        $periodo = $nombre === 'Deudor' ? '2026-09' : today()->format('Y-m');
        App\Models\DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $periodo, 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    }
    if ($nombre === 'Nuevo') {
        foreach ([1, 2] as $dias) {
            $anterior = App\Models\Clase::create(['grupo_id' => $g->id, 'fecha' => today()->subDays($dias)->toDateString(), 'hora_inicio' => '18:00', 'hora_fin' => '19:00']);
            App\Models\Asistencia::create(['clase_id' => $anterior->id, 'alumno_id' => $a->id, 'presente' => true]);
        }
    }
    $alumnos[$nombre] = $a->id;
}
$condiciones = app(App\Services\ExcepcionCobranzaService::class)->condicionesAsistencia(App\Models\Alumno::all(), $clase);
$datos = ['base' => 'wings_testing_codex', 'fecha' => today()->toDateString(), 'clase_id' => $clase->id,
    'alumnos' => $alumnos, 'condiciones' => $condiciones, 'excepciones_previas' => App\Models\ExcepcionCobranza::count()];
file_put_contents(__DIR__.'/fixture-navegador.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'Fixture B3 preparado solo en wings_testing_codex.'.PHP_EOL;
