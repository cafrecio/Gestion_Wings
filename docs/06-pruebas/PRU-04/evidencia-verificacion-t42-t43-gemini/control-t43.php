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
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use App\Services\LiquidacionService;
use App\Services\LiquidacionPagoService;
use App\Services\PartesPagoCuotaService;
use App\Services\ReciboService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "--- INICIANDO VERIFICACIÓN COMPLETA T43 ---\n";
$resultados = [];

DB::beginTransaction();
try {
    $admin = User::firstOrCreate(['email' => 'admin@wings.test'], [
        'name' => 'Admin Wings', 'password' => bcrypt('password'),
    ]);
    $admin->rol = 'ADMIN';
    $admin->activo = true;
    $admin->save();

    $adminInactivo = User::create([
        'name' => 'Admin Inactivo', 'email' => 'admin_inactivo@wings.test', 'password' => bcrypt('password'),
    ]);
    $adminInactivo->rol = 'ADMIN';
    $adminInactivo->activo = false;
    $adminInactivo->save();

    $operativa = User::create([
        'name' => 'Operativa Test', 'email' => 'operativa@wings.test', 'password' => bcrypt('password'),
    ]);
    $operativa->rol = 'OPERATIVO';
    $operativa->activo = true;
    $operativa->save();
    $deporte = Deporte::firstOrCreate(['nombre' => 'Acrobacia'], ['tipo_liquidacion' => 'COMISION']);
    $profesor = Profesor::create([
        'nombre' => 'Sofia', 'apellido' => 'Profe', 'dni' => '11223344', 'fecha_nacimiento' => '1992-05-10',
        'direccion' => 'Av Central 456', 'localidad' => 'Posadas', 'telefono' => '998877', 'email' => 'sofia@wings.test',
        'deporte_id' => $deporte->id, 'activo' => true, 'porcentaje_comision' => 20.0,
    ]);
    $nivel = Nivel::firstOrCreate(['nombre' => 'Acro Inicial']);
    $grupo = Grupo::firstOrCreate(['deporte_id' => $deporte->id], ['dias' => 'Martes', 'nivel_id' => $nivel->id]);
    $plan = GrupoPlan::firstOrCreate(['grupo_id' => $grupo->id], ['nombre' => 'Plan Acro', 'precio_mensual' => 30000, 'clases_por_semana' => 2]);
    $alumno = Alumno::create([
        'nombre' => 'Belen', 'apellido' => 'Alumna', 'dni' => '55667788', 'fecha_nacimiento' => '2012-03-15',
        'fecha_ingreso' => '2026-01-01', 'celular' => '445566', 'direccion' => 'Calle Sur 789', 'localidad' => 'Posadas',
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'grupo_plan_id' => $plan->id, 'activo' => true,
    ]);

    // Asistencia en octubre
    $clase = Clase::create([
        'deporte_id' => $deporte->id, 'grupo_id' => $grupo->id, 'fecha' => '2026-10-14',
        'hora_inicio' => '15:00', 'hora_fin' => '16:00', 'cancelada' => false,
    ]);
    $clase->profesores()->attach($profesor->id);
    Asistencia::create(['clase_id' => $clase->id, 'alumno_id' => $alumno->id, 'presente' => true]);

    // Deudas: Agosto (atrasada, $10.000), Octubre (del mes, $10.000), Noviembre (adelantada, $10.000)
    $dAgosto = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-08', 'mes' => 8, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
    $dOctubre = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-10', 'mes' => 10, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);
    $dNoviembre = DeudaCuota::create(['alumno_id' => $alumno->id, 'periodo' => '2026-11', 'mes' => 11, 'anio' => 2026, 'monto_original' => 10000, 'monto_actual' => 10000, 'estado' => 'PENDIENTE']);

    // Pago cobrado el 2026-10-18
    $pago = Pago::create([
        'alumno_id' => $alumno->id, 'monto_base' => 30000, 'porcentaje_aplicado' => 100,
        'monto_total' => 30000, 'monto_final' => 30000, 'monto_cuota' => 30000,
        'fecha_pago' => '2026-10-18', 'mes' => 10, 'anio' => 2026, 'estado' => Pago::ESTADO_COMPLETADO,
    ]);
    $pago->deudasCuota()->attach($dAgosto->id, ['monto_aplicado' => 10000]);
    $pago->deudasCuota()->attach($dOctubre->id, ['monto_aplicado' => 10000]);
    $pago->deudasCuota()->attach($dNoviembre->id, ['monto_aplicado' => 10000]);

    $liqService = app(LiquidacionService::class);
    $liq = Liquidacion::create([
        'profesor_id' => $profesor->id, 'mes' => 10, 'anio' => 2026, 'tipo' => 'COMISION',
        'porcentaje_comision_aplicado' => 20.0, 'estado' => Liquidacion::ESTADO_ABIERTA,
    ]);
    $totalCalc = $liqService->calcularLiquidacionComision($liq, $profesor, 10, 2026);
    $liq->update(['total_calculado' => $totalCalc]);
    $liq->refresh();

    // 1. Origen de cada parte en liquidación de Octubre:
    // Solo Agosto (atrasado) entra como PENDIENTE.
    // Octubre (del mes) entra directo (decision_atrasada = null, incluida).
    // Noviembre (adelantada) NO entra en octubre (va a nov).
    $detalles = $liq->detalles;
    $detAtrasada = $detalles->firstWhere('periodo_cuota', '2026-08');
    $detDelMes = $detalles->firstWhere('periodo_cuota', '2026-10');
    $detAdelantada = $detalles->firstWhere('periodo_cuota', '2026-11');

    $checkOrigenes = ($detAtrasada !== null && $detAtrasada->origen_cuota === 'atrasado' && $detAtrasada->decision_atrasada === 'PENDIENTE'
        && $detDelMes !== null && $detDelMes->origen_cuota === 'del_mes' && $detDelMes->decision_atrasada === null
        && $detAdelantada === null);
    echo "1. Orígenes y estado inicial: " . ($checkOrigenes ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['origenes_partes'] = $checkOrigenes;

    // 2. Importes iniciales: total_calculado = 4000, total_atrasadas_excluidas = 2000, total_confirmado = 2000
    $checkImportesIniciales = ($liq->total_calculado == 4000 && $liq->total_atrasadas_excluidas == 2000 && $liq->total_confirmado == 2000);
    echo "2. Importes con atrasada PENDIENTE (confirmado excluye pendientes): " . ($checkImportesIniciales ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['importes_iniciales'] = $checkImportesIniciales;

    // 3. Cierre y pago bloqueados mientras quede una PENDIENTE
    $bloqueoCierre = false;
    try {
        $liqService->cerrarLiquidacion($liq->id);
    } catch (\Exception $e) {
        $bloqueoCierre = str_contains($e->getMessage(), 'Primero validá o rechazá');
    }
    echo "3. Cierre bloqueado con atrasada pendiente: " . ($bloqueoCierre ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['bloqueo_cierre'] = $bloqueoCierre;

    // 4. Permisos: Operativa y Admin inactivo son rechazados
    $permisoOpRechazado = false;
    try {
        $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'APROBADA', $operativa->id, 'PENDIENTE');
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        $permisoOpRechazado = true;
    }

    $permisoInactivoRechazado = false;
    try {
        $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'APROBADA', $adminInactivo->id, 'PENDIENTE');
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        $permisoInactivoRechazado = true;
    }

    // Detalle de otra liquidación rechazado
    $otraLiq = Liquidacion::create([
        'profesor_id' => $profesor->id, 'mes' => 9, 'anio' => 2026, 'tipo' => 'COMISION',
        'porcentaje_comision_aplicado' => 20.0, 'estado' => Liquidacion::ESTADO_ABIERTA,
    ]);
    $detalleAjenoRechazado = false;
    try {
        $liqService->decidirAtrasada($otraLiq->id, $detAtrasada->id, 'APROBADA', $admin->id, 'PENDIENTE');
    } catch (\Exception $e) {
        $detalleAjenoRechazado = true;
    }
    $checkPermisos = ($permisoOpRechazado && $permisoInactivoRechazado && $detalleAjenoRechazado);
    echo "4. Permisos (operativa, admin inactivo y detalle ajeno rechazados): " . ($checkPermisos ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['permisos_estrictos'] = $checkPermisos;

    // 5. Concurrencia y formulario viejo: si decision_esperada no coincide, lanza validación
    $formularioViejoRechazado = false;
    try {
        $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'APROBADA', $admin->id, 'RECHAZADA'); // espera RECHAZADA pero está PENDIENTE
    } catch (\Illuminate\Validation\ValidationException $e) {
        $formularioViejoRechazado = true;
    }
    echo "5. Protección contra formulario viejo / conflicto concurrente: " . ($formularioViejoRechazado ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['formulario_viejo_concurrencia'] = $formularioViejoRechazado;

    // 6. Decisión: RECHAZAR la atrasada
    $liq = $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'RECHAZADA', $admin->id, 'PENDIENTE');
    $detAtrasada->refresh();
    $checkRechazada = ($detAtrasada->decision_atrasada === 'RECHAZADA' && $detAtrasada->decision_por === $admin->id
        && $liq->total_atrasadas_excluidas == 2000 && $liq->total_confirmado == 2000);
    echo "6. Rechazar atrasada (permanece excluida de confirmado): " . ($checkRechazada ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['decision_rechazada'] = $checkRechazada;

    // Auditoría permanente insertada
    $auditoriaCount1 = DB::table('liquidacion_decisiones_atrasadas')->where('liquidacion_id', $liq->id)->count();
    $aud1 = DB::table('liquidacion_decisiones_atrasadas')->where('liquidacion_id', $liq->id)->first();
    $checkAuditoria1 = ($auditoriaCount1 === 1 && $aud1->anterior === 'PENDIENTE' && $aud1->nueva === 'RECHAZADA' && $aud1->admin_id === $admin->id);
    echo "7. Auditoría permanente de decisión registrada: " . ($checkAuditoria1 ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['auditoria_permanente'] = $checkAuditoria1;

    // Repetir la misma decisión no duplica auditoría (idempotencia)
    $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'RECHAZADA', $admin->id, 'RECHAZADA');
    $auditoriaCountRepetida = DB::table('liquidacion_decisiones_atrasadas')->where('liquidacion_id', $liq->id)->count();
    $checkIdempotencia = ($auditoriaCountRepetida === 1);
    echo "8. Idempotencia: repetir misma decisión no duplica auditoría: " . ($checkIdempotencia ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['idempotencia_auditoria'] = $checkIdempotencia;

    // 7. Decisión: VALIDAR la atrasada
    $liq = $liqService->decidirAtrasada($liq->id, $detAtrasada->id, 'APROBADA', $admin->id, 'RECHAZADA');
    $detAtrasada->refresh();
    $checkAprobada = ($detAtrasada->decision_atrasada === 'APROBADA' && $liq->total_atrasadas_excluidas == 0 && $liq->total_confirmado == 4000);
    echo "9. Validar atrasada (se suma al confirmado): " . ($checkAprobada ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['decision_aprobada'] = $checkAprobada;

    // 8. Recálculo conserva decisión si condiciones económicas coinciden
    $liqRecalculada = $liqService->recalcularLiquidacion($liq->id);
    $detAtrasadaPostRecalc = $liqRecalculada->detalles()->firstWhere('periodo_cuota', '2026-08');
    $checkRecalculoConserva = ($detAtrasadaPostRecalc !== null && $detAtrasadaPostRecalc->decision_atrasada === 'APROBADA'
        && $liqRecalculada->total_confirmado == 4000);
    echo "10. Recálculo conserva decisión previa coincidente: " . ($checkRecalculoConserva ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['recalculo_conserva_decision'] = $checkRecalculoConserva;

    // Cambiar la base económica de la cuota en el pago hace que al recalcular exija decidir de nuevo
    $pago->deudasCuota()->updateExistingPivot($dAgosto->id, ['monto_aplicado' => 12000]);
    $liqRecalculada2 = $liqService->recalcularLiquidacion($liq->id);
    $detAtrasadaBaseCambiada = $liqRecalculada2->detalles()->firstWhere('periodo_cuota', '2026-08');
    $checkRecalculoCambioBase = ($detAtrasadaBaseCambiada !== null && $detAtrasadaBaseCambiada->decision_atrasada === 'PENDIENTE');
    echo "11. Recálculo ante cambio económico vuelve a PENDIENTE: " . ($checkRecalculoCambioBase ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['recalculo_cambio_economico'] = $checkRecalculoCambioBase;

    // Restaurar base a 10000 y aprobar de nuevo para probar cierre, pago y recibo
    $pago->deudasCuota()->updateExistingPivot($dAgosto->id, ['monto_aplicado' => 10000]);
    $liq = $liqService->recalcularLiquidacion($liq->id);
    $detAtrasadaFinal = $liq->detalles()->firstWhere('periodo_cuota', '2026-08');
    $liq = $liqService->decidirAtrasada($liq->id, $detAtrasadaFinal->id, 'APROBADA', $admin->id, 'PENDIENTE');

    // Monto final A23 absoluto: fijamos $5.000 (total_confirmado es $4.000 -> ajuste $1.000)
    $liq->ajustarMontoFinal('5000', $admin->id, 'Ajuste de prueba A23', (string) $liq->monto_a_pagar);
    $liq->refresh();
    $checkMontoFinalA23 = ($liq->monto_a_pagar == 5000 && $liq->total_confirmado == 4000);
    echo "12. Monto final A23 respetado como total absoluto: " . ($checkMontoFinalA23 ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['monto_final_a23'] = $checkMontoFinalA23;

    // 9. Cierre de liquidación
    Carbon::setTestNow('2026-11-01 10:00:00'); // ya terminó octubre
    $liqCerrada = $liqService->cerrarLiquidacion($liq->id);
    $checkCierre = ($liqCerrada->estado === Liquidacion::ESTADO_CERRADA);
    echo "13. Cierre exitoso tras resolver atrasadas: " . ($checkCierre ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['cierre_exitoso'] = $checkCierre;

    // Modificar detalles en cerrada está bloqueado
    $bloqueoModificarCerrada = false;
    try {
        $detAtrasadaFinal->update(['decision_atrasada' => 'RECHAZADA']);
    } catch (\Exception $e) {
        $bloqueoModificarCerrada = true;
    }
    echo "14. Bloqueo de modificación en cerrada: " . ($bloqueoModificarCerrada ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['bloqueo_modificar_cerrada'] = $bloqueoModificarCerrada;

    // 10. Pago de liquidación: registra monto_a_pagar ($5.000) en Cashflow exactamente una vez
    $tipoCaja = TipoCaja::firstOrCreate(['nombre' => 'Transferencia Bancaria'], ['activo' => true, 'saldo_inicial' => 100000]);
    $rubroSueldos = Rubro::firstOrCreate(['nombre' => 'Sueldos Profesores'], ['tipo' => 'EGRESO']);
    $subSueldo = Subrubro::firstOrCreate(['nombre' => 'Sueldo - Docente, Ana'], ['rubro_id' => $rubroSueldos->id, 'tipo' => 'EGRESO', 'activo' => true]);

    $pagoService = app(LiquidacionPagoService::class);
    $pagoService->marcarComoPagada($liqCerrada->id, [
        'fecha_pago' => '2026-11-02', 'tipo_caja_id' => $tipoCaja->id,
        'subrubro_id' => $subSueldo->id, 'admin_id' => $admin->id,
    ]);
    $liqCerrada->refresh();

    $movsCF = \App\Models\CashflowMovimiento::where('referencia_tipo', \App\Models\CashflowMovimiento::REF_LIQUIDACION)
        ->where('referencia_id', $liqCerrada->id)->get();
    $checkPagoCashflow = ($liqCerrada->estaPagada() && $movsCF->count() === 1 && $movsCF->first()->monto == -5000);
    echo "15. Pago registrado en Cashflow con monto exacto autorizado: " . ($checkPagoCashflow ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['pago_cashflow_exacto'] = $checkPagoCashflow;

    // 11. Recibo PDF generado con DomPDF
    $reciboService = app(ReciboService::class);
    $rutaPdf = $reciboService->generarReciboLiquidacion($liqCerrada->id, true);
    $pdfContenido = Storage::get($rutaPdf);
    $checkPdfGenerado = (strlen($pdfContenido) > 1000 && str_starts_with($pdfContenido, '%PDF-'));

    // Guardar copia local en evidencia para extracción de texto
    $pdfPathLocal = __DIR__ . '/recibo-t43-verificacion.pdf';
    file_put_contents($pdfPathLocal, $pdfContenido);

    echo "16. Recibo PDF generado correctamente: " . ($checkPdfGenerado ? "PASÓ" : "FALLÓ") . "\n";
    $resultados['recibo_pdf_generado'] = $checkPdfGenerado;

} finally {
    DB::rollBack();
}

file_put_contents(
    __DIR__ . '/resultado-control-t43.json',
    json_encode(['resultados' => $resultados, 'fecha' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\nDictamen parcial T43: " . (!in_array(false, $resultados, true) ? "TODOS LOS PUNTOS APROBADOS" : "HAY FALLAS") . "\n";
