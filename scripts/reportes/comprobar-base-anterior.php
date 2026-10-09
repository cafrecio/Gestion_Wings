<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (\Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'wings_testing_codex') {
    fwrite(STDERR, "Solo se permite wings_testing_codex. No se modificó ninguna base.\n");
    exit(1);
}
// Ensayo destructivo solamente sobre la base descartable: esquema físicamente
// anterior, no un mock. No ejecutar mientras corre la suite o se mira el escenario.
\Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
\Illuminate\Support\Facades\Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
if (\Illuminate\Support\Facades\Schema::hasTable('reporte_eventos')) throw new RuntimeException('No se volvió al esquema anterior.');
(new \Database\Seeders\CatalogosSeeder())->run();
$admin = \App\Models\User::factory()->create(['rol' => 'ADMIN', 'activo' => true]);
$deporte = \App\Models\Deporte::firstOrFail();
$grupo = \App\Models\Grupo::create(['deporte_id' => $deporte->id, 'nivel_id' => \App\Models\Nivel::first()->id, 'activo' => true]);
$alumno = \App\Models\Alumno::create(['nombre' => 'Anterior', 'apellido' => 'Prueba', 'dni' => '30009999', 'fecha_nacimiento' => '2000-01-01',
    'celular' => '1100000000', 'fecha_alta' => today(), 'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'activo' => true]);
$deuda = \App\Models\DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => today()->format('Y-m'),
    'monto_original' => 100, 'monto_pagado' => 0, 'estado' => 'PENDIENTE']);
$deuda->update(['monto_original' => 200]);
$tipo = \App\Models\TipoCaja::where('abreviatura', 'EFT')->firstOrFail();
\App\Models\CashflowMovimiento::create(['fecha' => today(), 'tipo_caja_id' => $tipo->id,
    'subrubro_id' => \App\Models\Subrubro::where('nombre', 'Cuota Mensual')->firstOrFail()->id,
    'monto' => 100, 'usuario_admin_id' => $admin->id,
    'reporte_tipo' => 'INGRESO', 'reporte_clasificacion' => 'NEGOCIO', 'reporte_deporte_id' => $deporte->id]);
$profesor = \App\Models\Profesor::create(['nombre' => 'Docente', 'apellido' => 'Anterior ficticio', 'dni' => '31009999',
    'fecha_nacimiento' => '1980-01-01', 'direccion' => 'Ficticia', 'localidad' => 'Prueba', 'valor_hora' => 50, 'deporte_id' => $deporte->id, 'activo' => true]);
$sub = app(\App\Services\SubrubroSueldoService::class)->paraProfesor($profesor);
$liq = \App\Models\Liquidacion::create(['profesor_id' => $profesor->id, 'mes' => today()->month, 'anio' => today()->year,
    'tipo' => 'HORA', 'total_calculado' => 50, 'estado' => 'CERRADA', 'estado_pago' => 'PENDIENTE']);
(new \App\Services\LiquidacionPagoService())->marcarComoPagada($liq->id, ['admin_id' => $admin->id, 'fecha_pago' => today()->toDateString(),
    'tipo_caja_id' => $tipo->id, 'subrubro_id' => $sub->id]);
if ($deuda->fresh()->monto_original !== '200.00' || $liq->fresh()->estado_pago !== 'PAGADA') throw new RuntimeException('Falló el recorrido anterior.');
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
if ($sub->fresh()->clasificacion_resultado !== 'NEGOCIO') throw new RuntimeException('No clasificó el sueldo preexistente por FK.');
if (!\Illuminate\Support\Facades\DB::table('reporte_eventos')->where('deuda_cuota_id', $deuda->id)->where('delta_centavos', 20000)->exists()) {
    throw new RuntimeException('No dejó saldo conocido de cuota anterior.');
}
echo "Esquema anterior físico: cuota, cashflow y sueldo correctos. Migración con sueldo/cuota preexistentes: correcta.\n";
