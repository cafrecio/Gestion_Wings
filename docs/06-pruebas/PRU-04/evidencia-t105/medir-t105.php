<?php

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\{Alumno, User, TipoCaja, Pago, GrupoPlan, Subrubro, Rubro, CajaOperativa};
use App\Services\{PagoCuotaService, CajaService, ReciboService};
use Tests\Support\CajaDeclarada;

$outputDir = __DIR__;
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

echo "=== INICIANDO MEDICIONES T105 ===\n\n";

// Función para medir generación de PDF con y sin subsetting
function medirPdf(string $vista, array $datos, string $nombreBase, string $outputDir): array
{
    // 1. SIN RECORTE (enable_font_subsetting = false)
    Config::set('dompdf.options.enable_font_subsetting', false);
    $t0 = microtime(true);
    $pdfSin = Pdf::loadView($vista, $datos)->setPaper('A5', 'portrait')->output();
    $tSin = (microtime(true) - $t0) * 1000;
    $bytesSin = strlen($pdfSin);
    file_put_contents("{$outputDir}/{$nombreBase}-sin-recorte.pdf", $pdfSin);

    // 2. CON RECORTE (enable_font_subsetting = true)
    Config::set('dompdf.options.enable_font_subsetting', true);
    $t1 = microtime(true);
    $pdfCon = Pdf::loadView($vista, $datos)->setPaper('A5', 'portrait')->output();
    $tCon = (microtime(true) - $t1) * 1000;
    $bytesCon = strlen($pdfCon);
    file_put_contents("{$outputDir}/{$nombreBase}-con-recorte.pdf", $pdfCon);

    $ahorroPorc = round((1 - ($bytesCon / $bytesSin)) * 100, 1);

    return [
        'bytes_sin' => $bytesSin,
        'kb_sin' => round($bytesSin / 1024, 1),
        'ms_sin' => round($tSin, 1),
        'bytes_con' => $bytesCon,
        'kb_con' => round($bytesCon / 1024, 1),
        'ms_con' => round($tCon, 1),
        'ahorro_porc' => $ahorroPorc,
    ];
}

// ── 1. RECIBO DE CUOTA NORMAL ───────────────────────────────────────────
$fecha = Carbon::create(2026, 11, 1, 10, 30);
$datosCuota = [
    'numero_recibo' => 'CUOTA-1001',
    'fecha_emision' => $fecha,
    'fecha_pago' => $fecha,
    'anulado' => false,
    'fecha_cancelacion' => null,
    'cancelado_por' => 'Administración',
    'motivo_cancelacion' => null,
    'alumno' => [
        'nombre' => 'Begoña Núñez Pérez',
        'dni' => '45.123.456',
        'deporte' => 'Patín Artístico – Competición',
    ],
    'periodos' => [
        [
            'periodo' => '2026-10',
            'periodo_texto' => 'Octubre 2026',
            'monto_aplicado' => 35000.00,
        ],
    ],
    'monto_total' => 35000.00,
    'medio_cobro' => [
        'tipo_caja' => 'Efectivo',
        'origen' => 'Operativo',
    ],
    'observaciones' => 'Pago realizado en término con bonificación del 10%. Caracteres: áéíóú ÁÉÍÓÚ ñÑ $ – · / : , . %',
];

$resCuota = medirPdf('pdfs.recibo-cuota', $datosCuota, 'recibo-cuota', $outputDir);
echo "1. Recibo de Cuota Normal:\n";
echo "   - Sin recorte: {$resCuota['kb_sin']} KB ({$resCuota['ms_sin']} ms)\n";
echo "   - Con recorte: {$resCuota['kb_con']} KB ({$resCuota['ms_con']} ms)\n";
echo "   - Ahorro: {$resCuota['ahorro_porc']}%\n\n";

// ── 2. RECIBO DE CUOTA ANULADA ──────────────────────────────────────────
$datosCuotaAnulada = array_merge($datosCuota, [
    'numero_recibo' => 'CUOTA-1001-ANULADA',
    'anulado' => true,
    'fecha_cancelacion' => Carbon::create(2026, 11, 2, 14, 15),
    'cancelado_por' => 'Carlos Administrador',
    'motivo_cancelacion' => 'Error involuntario de tipeo en el medio de pago: era Transferencia Bancaria y se marcó Efectivo. Rehecho.',
    'observaciones' => "Pago original anulado.\nMotivo de anulacion: Error involuntario de tipeo en el medio de pago: era Transferencia Bancaria y se marcó Efectivo. Rehecho.",
]);

$resCuotaAnulada = medirPdf('pdfs.recibo-cuota', $datosCuotaAnulada, 'recibo-cuota-anulada', $outputDir);
echo "2. Recibo de Cuota Anulada:\n";
echo "   - Sin recorte: {$resCuotaAnulada['kb_sin']} KB ({$resCuotaAnulada['ms_sin']} ms)\n";
echo "   - Con recorte: {$resCuotaAnulada['kb_con']} KB ({$resCuotaAnulada['ms_con']} ms)\n";
echo "   - Ahorro: {$resCuotaAnulada['ahorro_porc']}%\n\n";

// ── 3. RECIBO DE LIQUIDACIÓN POR HORA ───────────────────────────────────
$datosLiqHora = [
    'numero_recibo' => 'LIQ-501',
    'fecha_emision' => $fecha,
    'fecha_pago' => $fecha,
    'profesor' => [
        'nombre' => 'María Belén Gómez Peña',
        'deporte' => 'Patín Artístico',
        'tipo_liquidacion' => 'HORA',
        'modalidad_texto' => 'Por Hora ($12,000.00 / hora)',
    ],
    'periodo' => [
        'mes' => 10,
        'anio' => 2026,
        'nombre' => 'Octubre 2026',
        'texto' => 'Octubre 2026',
        'conteo_texto' => '4 clases dictadas · 6 h',
    ],
    'monto_total' => 72000.00,
    'total_liquidado' => 72000.00,
    'total_calculado' => 72000.00,
    'ajuste_final' => 0.00,
    'medio_pago' => [
        'tipo_caja' => 'Banco Santander',
        'subrubro' => 'Sueldo Docente',
    ],
    'observaciones' => 'Liquidación mensual según cronograma oficial. Horas dictadas: 6 h. Suplencias incluidas.',
    'modalidad' => 'HORA',
    'desglose_por_cuota' => false,
    'detalles' => [
        ['fecha' => '05/10/2026', 'grupo' => 'Patín – Inicial', 'minutos' => 90, 'horas' => 1.5, 'estado' => 'Validada', 'subtotal' => 18000.00],
        ['fecha' => '12/10/2026', 'grupo' => 'Patín – Inicial', 'minutos' => 90, 'horas' => 1.5, 'estado' => 'Validada', 'subtotal' => 18000.00],
        ['fecha' => '19/10/2026', 'grupo' => 'Patín – Avanzado', 'minutos' => 90, 'horas' => 1.5, 'estado' => 'Validada', 'subtotal' => 18000.00],
        ['fecha' => '26/10/2026', 'grupo' => 'Patín – Avanzado', 'minutos' => 90, 'horas' => 1.5, 'estado' => 'Validada', 'subtotal' => 18000.00],
    ],
];

$resLiqHora = medirPdf('pdfs.recibo-liquidacion', $datosLiqHora, 'recibo-liquidacion-hora', $outputDir);
echo "3. Recibo de Liquidación por Hora:\n";
echo "   - Sin recorte: {$resLiqHora['kb_sin']} KB ({$resLiqHora['ms_sin']} ms)\n";
echo "   - Con recorte: {$resLiqHora['kb_con']} KB ({$resLiqHora['ms_con']} ms)\n";
echo "   - Ahorro: {$resLiqHora['ahorro_porc']}%\n\n";

// ── 4. RECIBO DE LIQUIDACIÓN POR COMISIÓN ───────────────────────────────
$datosLiqComision = [
    'numero_recibo' => 'LIQ-502',
    'fecha_emision' => $fecha,
    'fecha_pago' => $fecha,
    'profesor' => [
        'nombre' => 'Joaquín Álvarez Cañete',
        'deporte' => 'Gimnasia Artística',
        'tipo_liquidacion' => 'COMISION',
        'modalidad_texto' => 'Por Comisión (50.00% sobre cuotas cobradas)',
    ],
    'periodo' => [
        'mes' => 10,
        'anio' => 2026,
        'nombre' => 'Octubre 2026',
        'texto' => 'Octubre 2026',
        'conteo_texto' => '3 cuotas cobradas',
    ],
    'monto_total' => 45000.00,
    'total_liquidado' => 45000.00,
    'total_calculado' => 45000.00,
    'ajuste_final' => 0.00,
    'medio_pago' => [
        'tipo_caja' => 'Efectivo',
        'subrubro' => 'Honorarios Gimnasia',
    ],
    'observaciones' => 'Liquidación por porcentaje directo sobre cobros de cuotas del período.',
    'modalidad' => 'COMISION',
    'desglose_por_cuota' => true,
    'detalles' => [
        ['alumno' => 'Sofía Benítez', 'grupo' => 'Gimnasia – Nivel 1', 'periodo_cuota' => '2026-10', 'cuota_base' => 30000.00, 'porcentaje' => 50.00, 'subtotal' => 15000.00],
        ['alumno' => 'Mateo Fernández', 'grupo' => 'Gimnasia – Nivel 1', 'periodo_cuota' => '2026-10', 'cuota_base' => 30000.00, 'porcentaje' => 50.00, 'subtotal' => 15000.00],
        ['alumno' => 'Valentina Iñíguez', 'grupo' => 'Gimnasia – Nivel 2', 'periodo_cuota' => '2026-10', 'cuota_base' => 30000.00, 'porcentaje' => 50.00, 'subtotal' => 15000.00],
    ],
];

$resLiqComision = medirPdf('pdfs.recibo-liquidacion', $datosLiqComision, 'recibo-liquidacion-comision', $outputDir);
echo "4. Recibo de Liquidación por Comisión:\n";
echo "   - Sin recorte: {$resLiqComision['kb_sin']} KB ({$resLiqComision['ms_sin']} ms)\n";
echo "   - Con recorte: {$resLiqComision['kb_con']} KB ({$resLiqComision['ms_con']} ms)\n";
echo "   - Ahorro: {$resLiqComision['ahorro_porc']}%\n\n";

// ── 5. MEDICIÓN DE COBROS CON Y SIN GENERACIÓN DE PDF ───────────────────
echo "5. Medición del proceso de cobro:\n";

// Crear datos de prueba en la base wings_testing_gemini
$operativo = User::where('rol', User::ROL_OPERATIVO)->where('activo', true)->first();
if (!$operativo) {
    $operativo = User::factory()->create(['name' => 'Sandra Operativa', 'rol' => User::ROL_OPERATIVO, 'activo' => true]);
}
$rubroIngreso = Rubro::firstOrCreate(['nombre' => 'Ingresos Operativos'], ['tipo' => 'INGRESO']);
Subrubro::firstOrCreate(['nombre' => 'Cuota Mensual'], [
    'rubro_id' => $rubroIngreso->id,
    'permitido_para' => 'OPERATIVO',
    'afecta_caja' => true,
    'clasificacion_resultado' => 'NEGOCIO',
    'activo' => true,
]);

$efectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);

// Asegurar caja abierta
$caja = CajaOperativa::where('usuario_operativo_id', $operativo->id)->where('estado', 'ABIERTA')->first();
if (!$caja) {
    $caja = CajaDeclarada::crear($operativo->id, $efectivo->id);
    $caja->update(['efectivo_inicial' => '50000.00']);
}

// Alumno para el cobro
$deporte = \App\Models\Deporte::firstOrCreate(['nombre' => 'Patín']);
$nivel = \App\Models\Nivel::firstOrCreate(['nombre' => 'Inicial']);
$grupo = \App\Models\Grupo::firstOrCreate(['deporte_id' => $deporte->id, 'nivel_id' => $nivel->id]);
$plan = GrupoPlan::firstOrCreate(
    ['grupo_id' => $grupo->id, 'clases_por_semana' => 2],
    ['precio_mensual' => 30000.00, 'activo' => true]
);

$dniTest = '99' . mt_rand(100000, 999999);
$alumno = Alumno::create([
    'nombre' => 'Estudiante',
    'apellido' => 'Medición T105',
    'dni' => $dniTest,
    'celular' => '1144556677',
    'fecha_nacimiento' => '2005-05-15',
    'fecha_alta' => '2026-01-01',
    'deporte_id' => $deporte->id,
    'grupo_id' => $grupo->id,
    'activo' => true,
]);

\App\Models\AlumnoPlan::create([
    'alumno_id' => $alumno->id,
    'plan_id' => $plan->id,
    'fecha_desde' => '2026-01-01',
    'activo' => true,
]);

\App\Models\DeudaCuota::create([
    'alumno_id' => $alumno->id,
    'periodo' => '2026-11',
    'monto_original' => 30000.00,
    'saldo_pendiente' => 30000.00,
    'estado' => 'PENDIENTE',
]);

\App\Models\DeudaCuota::create([
    'alumno_id' => $alumno->id,
    'periodo' => '2026-12',
    'monto_original' => 30000.00,
    'saldo_pendiente' => 30000.00,
    'estado' => 'PENDIENTE',
]);

$datosPagoBase = [
    'alumno_id' => $alumno->id,
    'tipo_caja_id' => $efectivo->id,
    'usuario_operativo_id' => $operativo->id,
    'items' => [
        ['periodo' => '2026-11', 'monto' => 30000.00],
    ],
    'observaciones' => 'Cobro prueba T105',
];

// Medición A: PagoCuotaService tal como está resuelto por Laravel (reciboService = null)
$pagoServiceSinRecibo = app(PagoCuotaService::class);
$tCobro0 = microtime(true);
$resPago1 = $pagoServiceSinRecibo->registrarPagoCuotaOperativo($datosPagoBase);
$msCobroSinRecibo = round((microtime(true) - $tCobro0) * 1000, 1);
$pagoId1 = $resPago1['pago']->id;

// Medición B: PagoCuotaService con ReciboService inyectado explícitamente (generando PDF en afterCommit)
$reciboService = app(ReciboService::class);
$cajaService = app(CajaService::class);
$pagoServiceConRecibo = new PagoCuotaService($cajaService, $reciboService);

$datosPagoBase2 = $datosPagoBase;
$datosPagoBase2['items'] = [['periodo' => '2026-12', 'monto' => 30000.00]];
$datosPagoBase2['observaciones'] = 'Cobro con PDF en afterCommit';

$tCobro1 = microtime(true);
$resPago2 = $pagoServiceConRecibo->registrarPagoCuotaOperativo($datosPagoBase2);
$msCobroConRecibo = round((microtime(true) - $tCobro1) * 1000, 1);
$pagoId2 = $resPago2['pago']->id;

// Medición C: Generación del PDF bajo demanda vía ReciboService
$tPdfGen0 = microtime(true);
$rutaPdf1 = $reciboService->generarReciboCuota($pagoId1, true);
$msPdfGen1 = round((microtime(true) - $tPdfGen0) * 1000, 1);

// Segunda llamada (sale de archivo cacheado en Storage)
$tPdfGenCached = microtime(true);
$rutaPdfCached = $reciboService->generarReciboCuota($pagoId1, false);
$msPdfCached = round((microtime(true) - $tPdfGenCached) * 1000, 2);

echo "   - Cobro habitual en Wings (reciboService = null en contenedor): {$msCobroSinRecibo} ms\n";
echo "   - Cobro con ReciboService inyectado (generando PDF en afterCommit): {$msCobroConRecibo} ms\n";
echo "   - Primera generación del PDF bajo demanda (generarReciboCuota): {$msPdfGen1} ms\n";
echo "   - Segunda consulta del PDF (archivo cacheado en disco): {$msPdfCached} ms\n\n";

// Guardar resultados en JSON
$resultados = [
    'fecha_medicion' => now()->toDateTimeString(),
    'cuota_normal' => $resCuota,
    'cuota_anulada' => $resCuotaAnulada,
    'liquidacion_hora' => $resLiqHora,
    'liquidacion_comision' => $resLiqComision,
    'mediciones_cobro' => [
        'cobro_sin_recibo_inyectado_ms' => $msCobroSinRecibo,
        'cobro_con_recibo_inyectado_ms' => $msCobroConRecibo,
        'generacion_pdf_primera_vez_ms' => $msPdfGen1,
        'consulta_pdf_cacheado_ms' => $msPdfCached,
    ],
];
file_put_contents("{$outputDir}/resultados-medicion-t105.json", json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Mediciones guardadas en resultados-medicion-t105.json\n";
