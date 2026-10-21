<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\FormaPago;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionService;
use App\Services\PartesPagoCuotaService;
use App\Services\PagoCuotaService;
use App\Services\ReporteMensualService;
use App\Services\ReporteSueldosService;
use App\Services\HistorialAnaliticoReportesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "--- INICIANDO VERIFICACIÓN COMPLETA T42 ---\n";
$resultados = [];

// 1. Cobro mixto en octubre (agosto atrasado + octubre + noviembre adelantado)
DB::beginTransaction();
try {
    $deporte = Deporte::firstOrCreate(['nombre' => 'Tela'], ['tipo_liquidacion' => 'COMISION']);
    $profesor = Profesor::create([
        'nombre' => 'Ana', 'apellido' => 'Docente', 'dni' => '99887766', 'fecha_nacimiento' => '1990-01-01',
        'direccion' => 'Calle 123', 'localidad' => 'Posadas', 'telefono' => '123456', 'email' => 'ana@wings.test',
        'deporte_id' => $deporte->id, 'activo' => true, 'porcentaje_comision' => 20.0,
    ]);
    $nivel = \App\Models\Nivel::firstOrCreate(['nombre' => 'Inicial']);
    $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id], ['dias' => 'Lunes', 'nivel_id' => $nivel->id]);
    $plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id], ['nombre' => 'Plan Base', 'precio_mensual' => 30000, 'clases_por_semana' => 2]);
    $alumno = Alumno::create([
        'nombre' => 'Clara', 'apellido' => 'Alumna', 'dni' => '44332211', 'fecha_nacimiento' => '2010-01-01',
        'fecha_ingreso' => '2026-01-01', 'celular' => '123456', 'direccion' => 'Calle 123', 'localidad' => 'Posadas',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'grupo_plan_id' => $plan->id, 'activo' => true,
    ]);

    // Deudas: Agosto ($10.000), Octubre ($10.000), Noviembre ($10.000)
    $dAgosto = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-08', 'mes' => 8, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
    $dOctubre = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'mes' => 10, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
    $dNoviembre = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-11', 'mes' => 11, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);

    // Pago mixto en fecha 2026-10-15 por $30.000
    $pagoMixto = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 30000, 'porcentaje_aplicado' => 100,
        'monto_total' => 30000, 'monto_final' => 30000, 'monto_cuota' => 30000,
        'fecha_pago' => '2026-10-15', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $pagoMixto->deudasCuota()->attach($dAgosto->id, ['monto_aplicado' => 10000]);
    $pagoMixto->deudasCuota()->attach($dOctubre->id, ['monto_aplicado' => 10000]);
    $pagoMixto->deudasCuota()->attach($dNoviembre->id, ['monto_aplicado' => 10000]);

    $partesService = app(PartesPagoCuotaService::class);
    $partes = $partesService->partes($pagoMixto);

    // Verificar desglose:
    // Agosto -> periodo_comision: max('2026-10', '2026-08') = '2026-10', origen: 'atrasado'
    // Octubre -> periodo_comision: max('2026-10', '2026-10') = '2026-10', origen: 'del_mes'
    // Noviembre -> periodo_comision: max('2026-10', '2026-11') = '2026-11', origen: 'adelantado'
    $partesMap = collect($partes)->keyBy('periodo_cuota');
    $pAgosto = $partesMap->get('2026-08');
    $pOctubre = $partesMap->get('2026-10');
    $pNoviembre = $partesMap->get('2026-11');

    $checkMixto = ($pAgosto['periodo_comision'] === '2026-10' && $pAgosto['origen'] === 'atrasado'
        && $pOctubre['periodo_comision'] === '2026-10' && $pOctubre['origen'] === 'del_mes'
        && $pNoviembre['periodo_comision'] === '2026-11' && $pNoviembre['origen'] === 'adelantado');

    echo "1. Cobro mixto partes: " . ($checkMixto ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['cobro_mixto_partes'] = $checkMixto;

    // Asistencias:
    // En Octubre: clase con Ana donde asiste Clara
    $claseOctubre = Clase::create([
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha' => '2026-10-10',
        'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false,
    ]);
    $claseOctubre->profesores()->attach($profesor->id);
    Asistencia::create(['clase_id' => $claseOctubre->id, 'alumno_id' => $alumno->id, 'presente' => true]);

    // En Noviembre: sin asistencia aún
    $liqService = app(LiquidacionService::class);
    $liqOctubre = Liquidacion::create([
        'profesor_id' => $profesor->id, 'mes' => 10, 'anio' => 2026, 'tipo' => 'COMISION',
        'porcentaje_comision_aplicado' => 20.0, 'estado' => Liquidacion::ESTADO_ABIERTA,
    ]);
    $totalOctubre = $liqService->calcularLiquidacionComision($liqOctubre, $profesor, 10, 2026);
    $detallesOct = $liqOctubre->detalles()->get();

    // En octubre deben entrar Agosto ($10.000 @ 20% = $2.000) y Octubre ($10.000 @ 20% = $2.000) -> Total $4.000
    // Y Noviembre NO debe entrar en octubre
    $checkLiqOctubre = ($totalOctubre == 4000 && $detallesOct->count() === 2 && !$detallesOct->contains('periodo_cuota', '2026-11'));
    echo "2. Liquidación Octubre (asistencia presente, sin noviembre adelantado): " . ($checkLiqOctubre ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['liq_octubre'] = $checkLiqOctubre;

    // En Noviembre: probar SIN asistencia -> total 0
    $liqNoviembre = Liquidacion::create([
        'profesor_id' => $profesor->id, 'mes' => 11, 'anio' => 2026, 'tipo' => 'COMISION',
        'porcentaje_comision_aplicado' => 20.0, 'estado' => Liquidacion::ESTADO_ABIERTA,
    ]);
    $totalNovSinAsist = $liqService->calcularLiquidacionComision($liqNoviembre, $profesor, 11, 2026);
    $checkNovSinAsist = ($totalNovSinAsist == 0);
    echo "3. Liquidación Noviembre SIN asistencia (exige asistencia en mes que liquida): " . ($checkNovSinAsist ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['liq_nov_sin_asist'] = $checkNovSinAsist;

    // Agregar asistencia en Noviembre
    $claseNov = Clase::create([
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha' => '2026-11-05',
        'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'cancelada' => false,
    ]);
    $claseNov->profesores()->attach($profesor->id);
    Asistencia::create(['clase_id' => $claseNov->id, 'alumno_id' => $alumno->id, 'presente' => true]);

    $liqNoviembre->detalles()->delete();
    $totalNovConAsist = $liqService->calcularLiquidacionComision($liqNoviembre, $profesor, 11, 2026);
    $detallesNov = $liqNoviembre->detalles()->get();
    // En noviembre entra la parte adelantada ($10.000 @ 20% = $2.000)
    $checkNovConAsist = ($totalNovConAsist == 2000 && $detallesNov->count() === 1 && $detallesNov->first()->periodo_cuota === '2026-11');
    echo "4. Liquidación Noviembre CON asistencia (entra noviembre adelantado): " . ($checkNovConAsist ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['liq_nov_con_asist'] = $checkNovConAsist;

    // 2. Cambio de año: Diciembre cobra Enero
    $dEnero = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2027-01', 'mes' => 1, 'anio' => 2027, 'monto_original' => 15000, 'monto_actual' => 15000, 'estado' => 'PENDIENTE']);
    $pagoDiciembre = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 15000, 'porcentaje_aplicado' => 100,
        'monto_total' => 15000, 'monto_final' => 15000, 'monto_cuota' => 15000,
        'fecha_pago' => '2026-12-28', 'mes' => 12, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $pagoDiciembre->deudasCuota()->attach($dEnero->id, ['monto_aplicado' => 15000]);
    $partesDic = $partesService->partes($pagoDiciembre);
    $checkCambioAnio = (count($partesDic) === 1 && $partesDic[0]['periodo_comision'] === '2027-01' && $partesDic[0]['origen'] === 'adelantado');
    echo "5. Cambio de año (Dic cobra Ene): " . ($checkCambioAnio ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['cambio_anio'] = $checkCambioAnio;

    // 3. Pago sin imputaciones (conserva regla histórica)
    $pagoSinImputaciones = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 8000, 'porcentaje_aplicado' => 100,
        'monto_total' => 8000, 'monto_final' => 8000, 'monto_cuota' => 8000,
        'fecha_pago' => '2026-10-05', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $partesSinImp = $partesService->partes($pagoSinImputaciones);
    $checkSinImp = (count($partesSinImp) === 1 && $partesSinImp[0]['periodo_comision'] === '2026-10' && $partesSinImp[0]['periodo_cuota'] === null && $partesSinImp[0]['centavos'] === 800000);
    echo "6. Pago sin imputaciones (fallback histórico): " . ($checkSinImp ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['fallback_sin_imputaciones'] = $checkSinImp;

    // 4. Centavos exactos sin crear ni perder
    // Pago $100.01 dividido en 3 cuotas de diferentes periodos: 2026-05, 2026-06, 2026-07 @ 15%
    $dC1 = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-05', 'mes' => 5, 'anio' => 2026, 'monto_original' => 33.33, 'monto_actual' => 33.33, 'estado' => 'PENDIENTE']);
    $dC2 = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-06', 'mes' => 6, 'anio' => 2026, 'monto_original' => 33.34, 'monto_actual' => 33.34, 'estado' => 'PENDIENTE']);
    $dC3 = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-07', 'mes' => 7, 'anio' => 2026, 'monto_original' => 33.34, 'monto_actual' => 33.34, 'estado' => 'PENDIENTE']);
    $pagoCentavos = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 100.01, 'porcentaje_aplicado' => 100,
        'monto_total' => 100.01, 'monto_final' => 100.01, 'monto_cuota' => 100.01,
        'fecha_pago' => '2026-10-10', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $pagoCentavos->deudasCuota()->attach($dC1->id, ['monto_aplicado' => 33.33]);
    $pagoCentavos->deudasCuota()->attach($dC2->id, ['monto_aplicado' => 33.34]);
    $pagoCentavos->deudasCuota()->attach($dC3->id, ['monto_aplicado' => 33.34]);

    $partesCentavos = $partesService->partes($pagoCentavos);
    $sumaPartesCentavos = array_sum(array_column($partesCentavos, 'centavos'));
    $checkSumaCentavos = ($sumaPartesCentavos === 10001); // 100.01 * 100

    $repartoRefl = new \ReflectionMethod(LiquidacionService::class, 'repartirComision');
    $repartoRefl->setAccessible(true);
    $repartidas = $repartoRefl->invoke($liqService, $partesCentavos, 15.0);
    $sumaComisionCentavos = array_sum(array_column($repartidas, 'comision_centavos'));
    // Objetivo = intdiv(10001 * 1500 + 5000, 10000) = intdiv(15001500 + 5000, 10000) = 1500 centavos ($15.00)
    $checkRepartoCentavos = ($sumaComisionCentavos === 1500);
    echo "7. Conservación exacta de centavos en base y comisión: " . ($checkSumaCentavos && $checkRepartoCentavos ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['centavos_exactos'] = ($checkSumaCentavos && $checkRepartoCentavos);

} finally {
    DB::rollBack();
}

// 5. Verificación de ReporteMensualService e Inicio (dinero vs económico, inscripción no se traslada, anulación, asientos ambiguos)
DB::beginTransaction();
try {
    $deporte = Deporte::firstOrCreate(['nombre' => 'Tela'], ['tipo_liquidacion' => 'COMISION']);
    $nivel = \App\Models\Nivel::firstOrCreate(['nombre' => 'Inicial']);
    $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id], ['dias' => 'Lunes', 'nivel_id' => $nivel->id]);
    $plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id], ['nombre' => 'Plan Base', 'precio_mensual' => 30000, 'clases_por_semana' => 2]);
    $alumno = Alumno::create([
        'nombre' => 'Martin', 'apellido' => 'Reportes', 'dni' => '44332299', 'fecha_nacimiento' => '2010-01-01',
        'fecha_ingreso' => '2026-01-01', 'celular' => '123456', 'direccion' => 'Calle 123', 'localidad' => 'Posadas',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'grupo_plan_id' => $plan->id, 'activo' => true,
    ]);

    $dNov = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-11', 'mes' => 11, 'anio' => 2026, 'monto_original' => 25000, 'monto_actual' => 25000, 'estado' => 'PENDIENTE']);
    $pagoAdelantado = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 30000, 'porcentaje_aplicado' => 100,
        'monto_total' => 30000, 'monto_final' => 30000, 'monto_cuota' => 25000,
        'fecha_pago' => '2026-10-20', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $pagoAdelantado->deudasCuota()->attach($dNov->id, ['monto_aplicado' => 25000]);

    $rubroIngreso = Rubro::firstOrCreate(['nombre' => 'Ingresos'], ['tipo' => 'INGRESO']);
    $subCuota = Subrubro::firstOrCreate(['nombre' => 'Cuota Mensual'], ['rubro_id' => $rubroIngreso->id, 'tipo' => 'INGRESO', 'activo' => true]);
    $subInscrip = Subrubro::firstOrCreate(['nombre' => 'Inscripción'], ['rubro_id' => $rubroIngreso->id, 'tipo' => 'INGRESO', 'activo' => true]);
    $admin = User::firstOrCreate(['email' => 'admin@wings.test'], [
        'name' => 'Admin Test', 'password' => bcrypt('password'), 'rol' => 'ADMIN', 'activo' => true,
    ]);
    $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true, 'saldo_inicial' => 0]);

    // Asiento en Cashflow cobrado el 2026-10-20: Cuota $25.000 + Inscripción $5.000
    $cfCuota = \App\Models\CashflowMovimiento::create([
        'fecha' => '2026-10-20', 'tipo_caja_id' => $tipoCaja->id, 'subrubro_id' => $subCuota->id,
        'monto' => 25000, 'tipo' => 'INGRESO', 'referencia_tipo' => \App\Models\CashflowMovimiento::REF_PAGO_CUOTA,
        'referencia_id' => $pagoAdelantado->id, 'usuario_admin_id' => $admin->id,
    ]);
    $cfInscrip = \App\Models\CashflowMovimiento::create([
        'fecha' => '2026-10-20', 'tipo_caja_id' => $tipoCaja->id, 'subrubro_id' => $subInscrip->id,
        'monto' => 5000, 'tipo' => 'INGRESO', 'referencia_tipo' => \App\Models\CashflowMovimiento::REF_PAGO_CUOTA,
        'referencia_id' => $pagoAdelantado->id, 'usuario_admin_id' => $admin->id,
    ]);

    Carbon::setTestNow('2026-11-15 10:00:00');
    $repService = app(ReporteMensualService::class);
    $repOctubre = $repService->obtener('2026-10');
    $repNoviembre = $repService->obtener('2026-11');

    // Comprobar que en Octubre:
    // Dinero real cobrado en octubre = $30.000 (total_ingresos financiero o dinero)
    // Ingreso económico de cuota adelantada aparece en Noviembre ($25.000)
    // La inscripción ($5.000) se queda en Octubre
    $ingresosOct = collect($repOctubre['filas'] ?? []);
    $ingresosNov = collect($repNoviembre['filas'] ?? []);

    $cuotaEnNov = $ingresosNov->first(fn ($i) => str_contains($i['concepto'] ?? '', 'Cuota Mensual (11/2026)'));
    $inscripEnOct = $ingresosOct->first(fn ($i) => ($i['concepto'] ?? '') === 'Inscripción');
    $cuotaEnOct = $ingresosOct->first(fn ($i) => str_contains($i['concepto'] ?? '', 'Cuota Mensual'));

    $checkReportesSeparacion = ($cuotaEnNov !== null && $cuotaEnNov['centavos'] === 2500000
        && $inscripEnOct !== null && $inscripEnOct['centavos'] === 500000
        && $cuotaEnOct === null);

    echo "8. ReporteMensual: cuota adelantada trasladada a nov, inscripción conservada en oct: " . ($checkReportesSeparacion ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['reportes_separacion_economica'] = $checkReportesSeparacion;

    // 6. Asiento antiguo ambiguo (dos candidatos iguales conservan el mes original)
    // Creamos dos movimientos operativos idénticos en caja 99
    $cajaVieja = CajaOperativa::create([
        'usuario_operativo_id' => $admin->id, 'tipo_caja_id' => $tipoCaja->id, 'apertura_at' => '2026-01-10 08:00:00',
        'fecha_apertura' => '2026-01-10', 'estado' => CajaOperativa::ESTADO_VALIDADA,
    ]);
    $pagoViejo1 = Pago::create(['alumno_id' => $alumno->id, 'monto_base' => 20000, 'porcentaje_aplicado' => 100, 'monto_total' => 20000, 'monto_final' => 20000, 'monto_cuota' => 20000, 'fecha_pago' => '2026-01-10', 'mes' => 2, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO]);
    $pagoViejo2 = Pago::create(['alumno_id' => $alumno->id, 'monto_base' => 20000, 'porcentaje_aplicado' => 100, 'monto_total' => 20000, 'monto_final' => 20000, 'monto_cuota' => 20000, 'fecha_pago' => '2026-01-10', 'mes' => 2, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO]);

    $movOp1 = MovimientoOperativo::create([
        'caja_operativa_id' => $cajaVieja->id, 'tipo' => 'INGRESO', 'subrubro_id' => $subCuota->id,
        'tipo_caja_id' => $tipoCaja->id, 'monto' => 20000, 'fecha' => '2026-01-10', 'pago_id' => $pagoViejo1->id,
        'usuario_id' => $admin->id,
    ]);
    $movOp2 = MovimientoOperativo::create([
        'caja_operativa_id' => $cajaVieja->id, 'tipo' => 'INGRESO', 'subrubro_id' => $subCuota->id,
        'tipo_caja_id' => $tipoCaja->id, 'monto' => 20000, 'fecha' => '2026-01-10', 'pago_id' => $pagoViejo2->id,
        'usuario_id' => $admin->id,
    ]);

    // Asiento Cashflow antiguo sin movimiento_operativo_id que apunta a REF_CAJA
    $cfViejo = \App\Models\CashflowMovimiento::create([
        'fecha' => '2026-01-10', 'tipo_caja_id' => $tipoCaja->id, 'subrubro_id' => $subCuota->id,
        'monto' => 20000, 'tipo' => 'INGRESO', 'referencia_tipo' => \App\Models\CashflowMovimiento::REF_CAJA,
        'referencia_id' => $cajaVieja->id, 'movimiento_operativo_id' => null, 'usuario_admin_id' => $admin->id,
    ]);

    $antiguosRefl = new \ReflectionMethod(ReporteMensualService::class, 'pagosDeAsientosAntiguos');
    $antiguosRefl->setAccessible(true);
    $antiguos = $antiguosRefl->invoke($repService, collect([$cfViejo]));

    // Al haber 2 candidatos operativos con la misma clave ($candidatos->count() === 2),
    // $antiguos NO debe adivinar ni emparejar, conservando $antiguos[$cfViejo->id] vacío/null.
    $checkAmbiguo = !isset($antiguos[$cfViejo->id]);
    echo "9. Asiento antiguo ambiguo con dos candidatos conserva el mes original: " . ($checkAmbiguo ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['asiento_antiguo_ambiguo'] = $checkAmbiguo;

    // 7. Anulación reparte asiento y contraasiento en los mismos meses
    // Creamos pago con detalle de anulación y sus periodos guardados
    $pagoAnulado = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 20000, 'porcentaje_aplicado' => 100,
        'monto_total' => 20000, 'monto_final' => 20000, 'monto_cuota' => 20000,
        'fecha_pago' => '2026-10-10', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_ANULADO,
    ]);
    $pagoAnulado->detalle_anulacion = [
        'fecha' => '2026-10-25', 'usuario' => 'Admin', 'motivo' => 'Error',
        'periodos' => [
            ['deuda_id' => null, 'periodo' => '2026-11', 'monto_aplicado' => 20000],
        ],
    ];
    $pagoAnulado->save();
    $pagoAnulado->refresh();
    $partesAnulado = $partesService->partes($pagoAnulado);
    $checkAnulado = (count($partesAnulado) === 1 && $partesAnulado[0]['periodo_comision'] === '2026-11' && $partesAnulado[0]['periodo_cuota'] === '2026-11');
    echo "10. Pago anulado conserva desglose y periodo comision: " . ($checkAnulado ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['pago_anulado_desglose'] = $checkAnulado;

} finally {
    DB::rollBack();
}

file_put_contents(
    __DIR__ . '/resultado-control-t42.json',
    json_encode(['resultados' => $resultados, 'fecha' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\nDictamen parcial T42: " . (!in_array(false, $resultados, true) ? "TODOS LOS PUNTOS APROBADOS" : "HAY FALLAS") . "\n";
