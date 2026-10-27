<?php

/** Fixture de navegador local descartable. No contiene credenciales del club. */
$raiz = dirname(__DIR__, 4);
require $raiz.'/vendor/autoload.php';
if (getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'wings_testing_codex'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_URL') !== '') {
    throw new RuntimeException('Solo testing local, wings_testing_codex.');
}
$app = require $raiz.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') throw new RuntimeException('Base efectiva distinta.');
Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\CatalogosSeeder::class, '--force' => true]);
Illuminate\Support\Facades\Notification::fake();
App\Models\PrimeraCarga::whereKey(1)->update(['estado' => 'TERMINADA']);
$admin = App\Models\User::factory()->create(['name' => 'Dueño de prueba T88', 'email' => 'admin-t88@example.test', 'rol' => 'ADMIN', 'activo' => true]);
$op = App\Models\User::factory()->create(['name' => 'Operativa de prueba T88', 'email' => 'operativa-t88@example.test', 'rol' => 'OPERATIVO', 'activo' => true]);
$efectivo = App\Models\TipoCaja::firstOrFail();
app(App\Services\CajaService::class)->configurarMostrador($efectivo->id, $admin->id);
app(App\Services\CajaService::class)->abrirCajaOperativa($op->id, ['efectivo_inicial' => 1000, 'confirmacion' => 1, 'caja_origen_id' => null]);
$g = App\Models\Grupo::create(['deporte_id' => App\Models\Deporte::firstOrFail()->id, 'nivel_id' => App\Models\Nivel::firstOrFail()->id, 'activo' => true]);
$plan = App\Models\GrupoPlan::create(['grupo_id' => $g->id, 'clases_por_semana' => 2, 'precio_mensual' => 28000, 'activo' => true]);
$alumnos = [];
foreach (['Parcial' => ['2026-10'], 'Anteriores' => ['2026-03', '2026-05', '2026-10']] as $nombre => $periodos) {
    $a = App\Models\Alumno::create(['nombre' => $nombre, 'apellido' => 'Prueba', 'dni' => $nombre === 'Parcial' ? '31888001' : '31888002',
        'deporte_id' => $g->deporte_id, 'grupo_id' => $g->id, 'fecha_alta' => '2026-01-01', 'fecha_nacimiento' => '2000-01-01', 'celular' => '1100000000', 'activo' => true]);
    App\Models\AlumnoPlan::create(['alumno_id' => $a->id, 'plan_id' => $plan->id, 'fecha_desde' => '2026-01-01', 'activo' => true]);
    foreach ($periodos as $p) App\Models\DeudaCuota::create(['alumno_id' => $a->id, 'periodo' => $p, 'monto_original' => 28000, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
    $alumnos[$nombre] = $a->id;
}
$datos = ['base' => 'wings_testing_codex', 'entorno' => app()->environment(), 'alumnos' => $alumnos, 'efectivo' => $efectivo->id,
    'gasto' => App\Models\Subrubro::where('nombre', 'Limpieza')->value('id'), 'fecha' => today()->toDateString()];
file_put_contents(__DIR__.'/fixture-navegador.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'Fixture sintetico listo en wings_testing_codex.'.PHP_EOL;
