<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\User;
use App\Services\LiquidacionService;

echo "--- INICIANDO SEED T43 NAVEGADOR ---\n";

$admin = User::firstOrCreate(['email' => 'admin@wings.test'], [
    'name' => 'Admin Wings', 'password' => bcrypt('password'),
]);
$admin->rol = 'ADMIN';
$admin->activo = true;
$admin->save();

$deporte = Deporte::firstOrCreate(['nombre' => 'Acrobacia Browser'], ['tipo_liquidacion' => 'COMISION']);
$profesor = Profesor::create([
    'nombre' => 'Valeria', 'apellido' => 'Docente', 'dni' => '11992288', 'fecha_nacimiento' => '1991-07-20',
    'direccion' => 'Calle Browser 123', 'localidad' => 'Posadas', 'telefono' => '112233', 'email' => 'valeria@wings.test',
    'deporte_id' => $deporte->id, 'activo' => true, 'porcentaje_comision' => 20.0,
]);
$nivel = Nivel::firstOrCreate(['nombre' => 'Nivel Acro']);
$grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id], ['dias' => 'Miercoles', 'nivel_id' => $nivel->id]);
$plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id], ['nombre' => 'Plan Semanal', 'precio_mensual' => 30000, 'clases_por_semana' => 2]);
$alumno = Alumno::create([
    'nombre' => 'Camila', 'apellido' => 'Lopez', 'dni' => '66778899', 'fecha_nacimiento' => '2013-04-12',
    'fecha_ingreso' => '2026-01-01', 'celular' => '998877', 'direccion' => 'Calle 456', 'localidad' => 'Posadas',
    'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'grupo_plan_id' => $plan->id, 'activo' => true,
]);

// Asistencia en octubre
$clase = Clase::create([
    'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha' => '2026-10-15',
    'hora_inicio' => '16:00', 'hora_fin' => '17:00', 'cancelada' => false,
]);
$clase->profesores()->attach($profesor->id);
Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);

// Deudas: Julio ($10.000 atrasada), Agosto ($10.000 atrasada), Octubre ($10.000 del mes)
$dJulio = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-07', 'mes' => 7, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
$dAgosto = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-08', 'mes' => 8, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
$dOctubre = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'mes' => 10, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);

$pago = Pago::create([
    'alumno_id' => $alumno->id, 'monto_base' => 30000, 'porcentaje_aplicado' => 100,
    'monto_total' => 30000, 'monto_final' => 30000, 'monto_cuota' => 30000,
    'fecha_pago' => '2026-10-18', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
]);
$pago->deudasCuota()->attach($dJulio->id, ['monto_aplicado' => 10000]);
$pago->deudasCuota()->attach($dAgosto->id, ['monto_aplicado' => 10000]);
$pago->deudasCuota()->attach($dOctubre->id, ['monto_aplicado' => 10000]);

$liqService = app(LiquidacionService::class);
$liq = Liquidacion::create([
    'profesor_id' => $profesor->id, 'mes' => 10, 'anio' => 2026, 'tipo' => 'COMISION',
    'porcentaje_comision_aplicado' => 20.0, 'estado' => Liquidacion::ESTADO_ABIERTA,
]);
$totalCalc = $liqService->calcularLiquidacionComision($liq, $profesor, 10, 2026);
$liq->update(['total_calculado' => $totalCalc]);
$liq->refresh();

echo "Liquidación creada para prueba navegador: ID {$liq->id}\n";
echo "Detalles creados: {$liq->detalles->count()}\n";
echo "Total calculado: \${$liq->total_calculado}, Excluido: \${$liq->total_atrasadas_excluidas}, Confirmado: \${$liq->total_confirmado}\n";
file_put_contents(__DIR__ . '/liq-browser-id.txt', (string) $liq->id);
