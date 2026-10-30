<?php

putenv('DB_DATABASE=wings_manual_gemini');
$_ENV['DB_DATABASE'] = 'wings_manual_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
use App\Models\Pago;
use App\Models\PrimeraCarga;
use App\Models\Profesor;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

Carbon::setTestNow('2026-10-29 18:00:00');

$dirEvidencia = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
File::ensureDirectoryExists($dirEvidencia);

$dirImgAdmin = resource_path('manual/img/admin');
File::ensureDirectoryExists($dirImgAdmin);

// Usuario Admin
$admin = User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Carlos Freccero';
$admin->rol = User::ROL_ADMIN;
$admin->activo = true;
$admin->password = bcrypt('password');
$admin->save();

// Usuario Operativo
$operativo = User::firstOrNew(['email' => 'operativa@wings.test']);
$operativo->name = 'Sandra Operativa';
$operativo->rol = User::ROL_OPERATIVO;
$operativo->activo = true;
$operativo->password = bcrypt('password');
$operativo->save();

Auth::setUser($admin);
$sessionStore = app('session.store');
if (!$sessionStore->isStarted()) {
    $sessionStore->start();
}
View::share('errors', new \Illuminate\Support\ViewErrorBag);

// Cuentas / Tipos de caja
$tipoEfectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true, 'saldo_inicial' => 50000]);
$tipoBanco = TipoCaja::firstOrCreate(['nombre' => 'Banco Galicia'], ['activo' => true, 'saldo_inicial' => 250000]);
DB::table('caja_mostrador')->updateOrInsert(['id' => 1], ['tipo_caja_id' => $tipoEfectivo->id]);

// Catálogos
$deportePatin = Deporte::firstOrCreate(['nombre' => 'Patín Artístico'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$deporteFutbol = Deporte::firstOrCreate(['nombre' => 'Fútbol'], ['tipo_liquidacion' => 'COMISION', 'activo' => true]);

$nivelInicial = Nivel::firstOrCreate(['nombre' => 'Inicial']);
$nivelJuvenil = Nivel::firstOrCreate(['nombre' => 'Juveniles']);

$grupoPatin = Grupo::firstOrCreate(['deporte_id' => $deportePatin->id, 'nivel_id' => $nivelInicial->id], ['nombre' => 'Patín Escuela', 'activo' => true]);
$grupoFutbol = Grupo::firstOrCreate(['deporte_id' => $deporteFutbol->id, 'nivel_id' => $nivelJuvenil->id], ['nombre' => 'Fútbol Juveniles', 'activo' => true]);

$planPatin = GrupoPlan::firstOrCreate(['grupo_id' => $grupoPatin->id, 'clases_por_semana' => 2], ['precio_mensual' => 28000, 'activo' => true]);
$planFutbol = GrupoPlan::firstOrCreate(['grupo_id' => $grupoFutbol->id, 'clases_por_semana' => 3], ['precio_mensual' => 34000, 'activo' => true]);

// Rubros y Subrubros
$rubroRetiros = Rubro::firstOrCreate(['nombre' => 'Retiros'], ['tipo' => 'EGRESO', 'observacion' => 'Retiros de los dueños']);
$subrubroRetiro = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroRetiros->id, 'nombre' => 'Retiro de dueños'],
    ['permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true, 'clasificacion' => \App\Support\ClasificacionSubrubros::RETIRO]
);

$rubroAportes = Rubro::firstOrCreate(['nombre' => 'Aportes'], ['tipo' => 'INGRESO', 'observacion' => 'Aportes de capital']);
$subrubroAporte = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroAportes->id, 'nombre' => 'Aporte de dueños'],
    ['permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true, 'clasificacion' => \App\Support\ClasificacionSubrubros::APORTE]
);

$rubroEquipamiento = Rubro::firstOrCreate(['nombre' => 'Equipamiento'], ['tipo' => 'EGRESO', 'observacion' => '']);
$subrubroMateriales = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroEquipamiento->id, 'nombre' => 'Pelotas y conos de entrenamiento'],
    ['permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true, 'clasificacion' => \App\Support\ClasificacionSubrubros::NEGOCIO]
);

$rubroSueldos = Rubro::firstOrCreate(['nombre' => 'Sueldos'], ['tipo' => 'EGRESO', 'observacion' => '']);
$subrubroSueldoPatin = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroSueldos->id, 'nombre' => 'Sueldo - Méndez, Lucía'],
    ['permitido_para' => User::ROL_ADMIN, 'afecta_caja' => true, 'clasificacion' => \App\Support\ClasificacionSubrubros::NEGOCIO]
);

// Profesores
$profesorHora = Profesor::firstOrCreate(
    ['dni' => '28999888'],
    [
        'nombre' => 'Lucía', 'apellido' => 'Méndez', 'deporte_id' => $deportePatin->id,
        'fecha_nacimiento' => '1988-04-12', 'direccion' => 'Av. San Martín 1234', 'localidad' => 'Rosario',
        'valor_hora' => 4500, 'activo' => true, 'telefono' => '3415556677'
    ]
);

$profesorComision = Profesor::firstOrCreate(
    ['dni' => '29111222'],
    [
        'nombre' => 'Marcos', 'apellido' => 'Ríos', 'deporte_id' => $deporteFutbol->id,
        'fecha_nacimiento' => '1985-08-20', 'direccion' => 'Calle Corrientes 567', 'localidad' => 'Rosario',
        'porcentaje_comision' => 40, 'activo' => true, 'telefono' => '3416667788'
    ]
);

// Alumnos y cuotas
$alumno1 = Alumno::firstOrCreate(
    ['dni' => '47111222'],
    [
        'nombre' => 'Camila', 'apellido' => 'Suárez', 'deporte_id' => $deportePatin->id, 'grupo_id' => $grupoPatin->id,
        'fecha_nacimiento' => '2005-03-10', 'celular' => '3412345678', 'fecha_alta' => '2026-03-01', 'activo' => true
    ]
);

// Caja para validar/rechazar
$cajaCerrada = CajaOperativa::where('estado', 'CERRADA')->first();
if (!$cajaCerrada) {
    $cajaCerrada = \Tests\Support\CajaDeclarada::crear($operativo->id, $tipoEfectivo->id);
    $cajaCerrada->update([
        'estado' => 'CERRADA',
        'efectivo_inicial' => 12000,
        'efectivo_contado' => 45000,
        'cambio_retenido' => 12000,
        'efectivo_retirado' => 33000,
        'apertura_at' => Carbon::now()->subHours(8),
        'cierre_at' => Carbon::now()->subHours(1),
    ]);

    MovimientoOperativo::create([
        'caja_operativa_id' => $cajaCerrada->id,
        'tipo_caja_id' => $tipoEfectivo->id,
        'subrubro_id' => $subrubroMateriales->id,
        'monto' => 33000,
        'usuario_id' => $operativo->id,
        'fecha' => Carbon::now()->subHours(4),
        'estado' => MovimientoOperativo::ESTADO_ACTIVO,
        'observaciones' => 'Cobranza general del turno tarde',
    ]);
}

// Liquidación para auditar
$liquidacionHora = Liquidacion::firstOrCreate(
    ['profesor_id' => $profesorHora->id, 'mes' => 10, 'anio' => 2026],
    [
        'tipo' => 'HORA',
        'estado' => 'ABIERTA',
        'estado_pago' => 'PENDIENTE',
        'valor_hora_aplicado' => 4500,
        'total_horas' => 24,
        'monto_bruto' => 108000,
        'monto_a_pagar' => 108000,
        'created_at' => Carbon::now()->subDays(2),
    ]
);

$clase1 = Clase::firstOrCreate(
    ['grupo_id' => $grupoPatin->id, 'fecha' => '2026-10-05', 'hora_inicio' => '17:00:00'],
    ['hora_fin' => '18:00:00', 'validada_para_liquidacion' => true]
);
if (!$clase1->profesores()->where('profesores.id', $profesorHora->id)->exists()) {
    $clase1->profesores()->attach($profesorHora->id);
}

LiquidacionDetalle::firstOrCreate(
    ['liquidacion_id' => $liquidacionHora->id, 'referencia_id' => $clase1->id],
    [
        'tipo_referencia' => LiquidacionDetalle::TIPO_CLASE,
        'descripcion' => 'Clase de Patín Escuela (60 min)',
        'monto' => 4500,
        'minutos' => 60,
    ]
);

$publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
$publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';
$manualImgAdmin = 'file:///' . str_replace('\\', '/', resource_path('manual/img/admin')) . '/';

$sanitizar = function (string $html) use ($publicBuild, $publicImg, $manualImgAdmin): string {
    $html = preg_replace('#https?://[^/]+/build/#', $publicBuild, $html);
    $html = preg_replace('#https?://[^/]+/img/#', $publicImg, $html);
    $html = str_replace(['src="/build/', 'href="/build/'], ['src="' . $publicBuild, 'href="' . $publicBuild], $html);
    $html = str_replace(['src="/img/', 'href="/img/'], ['src="' . $publicImg, 'href="' . $publicImg], $html);
    $html = str_replace('/ayuda/img/admin/', $manualImgAdmin, $html);
    return $html;
};

$armarMarco375 = function (string $archivoHtml): string {
    return '<!doctype html><meta charset="utf-8">' . "\n" .
           '<body style="margin:0;background:#0f172a;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #334155;border-radius:14px;background:#fff;box-shadow:0 12px 30px rgba(0,0,0,0.6);"></iframe></body>' . "\n";
};

// Controladores
$primeraCargaCtrl = app(\App\Http\Controllers\PrimeraCargaWebController::class);
$primeraCargaServ = app(\App\Services\PrimeraCargaExcelService::class);
$cajaCtrl = app(\App\Http\Controllers\CajaWebController::class);
$cashflowCtrl = app(\App\Http\Controllers\CashflowWebController::class);
$transfCtrl = app(\App\Http\Controllers\TransferenciaCashflowWebController::class);
$liqCtrl = app(\App\Http\Controllers\LiquidacionWebController::class);
$reciboCtrl = app(\App\Http\Controllers\ReciboController::class);

echo "Generando vistas HTML para capturas de la Etapa B...\n";

// ──────────────────────────────────────────────────────────────────────────
// 1. Primera carga: Descargar plantilla
// ──────────────────────────────────────────────────────────────────────────
$req = Request::create('/sistema/primera-carga?paso=1', 'GET');
$req->setLaravelSession($sessionStore);
$sessionStore->put('primera_carga_paso', 1);
$html = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
File::put("$dirEvidencia/primera-carga-descargar-plantilla-paso1.html", $html);

$req = Request::create('/sistema/primera-carga?paso=2', 'GET');
$req->setLaravelSession($sessionStore);
$sessionStore->put('primera_carga_paso', 2);
$html = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
File::put("$dirEvidencia/primera-carga-descargar-plantilla-paso2.html", $html);

// ──────────────────────────────────────────────────────────────────────────
// 2. Primera carga: Subir y revisar
// ──────────────────────────────────────────────────────────────────────────
$req = Request::create('/sistema/primera-carga?paso=3', 'GET');
$req->setLaravelSession($sessionStore);
$sessionStore->put('primera_carga_paso', 3);
$sessionStore->forget('primera_carga_revision');
$htmlSubir1 = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
$htmlSubir1 = str_replace('</head>', '<style>#paso-1, #paso-2 { display: none !important; }</style></head>', $htmlSubir1);
File::put("$dirEvidencia/primera-carga-subir-revisar-paso1.html", $htmlSubir1);

// Con informe de revisión con errores
$sessionStore->put('primera_carga_revision', [
    'path' => 'demo.xlsx',
    'usuario_id' => $admin->id,
    'errores' => [
        ['fila' => 4, 'columna' => 'Fecha ingreso', 'celda' => 'E4', 'mensaje' => 'La fecha no puede ser futura.', 'esperado' => 'dd/mm/aaaa anterior o igual a hoy'],
        ['fila' => 8, 'columna' => 'Tutor', 'celda' => 'H8', 'mensaje' => 'El tutor es obligatorio para menores.', 'esperado' => 'Nombre y apellido del tutor'],
    ],
    'resumen' => ['alumnos' => 12, 'cuotas' => 15, 'inscripciones' => 2, 'cuotas_monto' => 380000, 'inscripcion_monto' => 30000, 'total' => 410000]
]);
$htmlSubir2 = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
$htmlSubir2 = str_replace('</head>', '<style>#paso-1, #paso-2 { display: none !important; }</style></head>', $htmlSubir2);
File::put("$dirEvidencia/primera-carga-subir-revisar-paso2.html", $htmlSubir2);

// ──────────────────────────────────────────────────────────────────────────
// 3. Primera carga: Confirmar importación
// ──────────────────────────────────────────────────────────────────────────
$sessionStore->put('primera_carga_paso', 4);
$sessionStore->put('primera_carga_revision', [
    'path' => 'demo.xlsx',
    'usuario_id' => $admin->id,
    'errores' => [],
    'resumen' => ['alumnos' => 35, 'cuotas' => 42, 'inscripciones' => 5, 'cuotas_monto' => 980000, 'inscripcion_monto' => 75000, 'total' => 1055000]
]);
$req = Request::create('/sistema/primera-carga?paso=4', 'GET');
$req->setLaravelSession($sessionStore);
$htmlConf1 = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
$htmlConf1 = str_replace('</head>', '<style>#paso-1, #paso-2, #paso-3 { display: none !important; }</style></head>', $htmlConf1);
File::put("$dirEvidencia/primera-carga-confirmar-paso1.html", $htmlConf1);

// Con formulario de confirmación visible
$htmlConf2 = str_replace('data-carga-form hidden', 'data-carga-form', $htmlConf1);
$htmlConf2 = str_replace('<div class="alumno-actions"><x-ds.button variant="primary" data-carga-confirmar>Cargar</x-ds.button>', '<div class="alumno-actions" style="display:none;"><x-ds.button variant="primary" data-carga-confirmar>Cargar</x-ds.button>', $htmlConf2);
File::put("$dirEvidencia/primera-carga-confirmar-paso2.html", $htmlConf2);

// ──────────────────────────────────────────────────────────────────────────
// 4. Primera carga: Deshacer
// ──────────────────────────────────────────────────────────────────────────
$pcEstado = PrimeraCarga::findOrFail(1);
$pcEstadoOrig = $pcEstado->estado;
$pcDetalleOrig = $pcEstado->detalle;

$pcEstado->update([
    'estado' => 'TERMINADA',
    'detalle' => ['resumen' => ['alumnos' => 35, 'cuotas' => 42, 'inscripciones' => 5, 'cuotas_monto' => 980000, 'inscripcion_monto' => 75000, 'total' => 1055000]]
]);
$req = Request::create('/sistema/primera-carga', 'GET');
$req->setLaravelSession($sessionStore);
$htmlDesh1 = $sanitizar($primeraCargaCtrl->index($req, $primeraCargaServ)->render());
$htmlDesh1 = str_replace('</head>', '<style>#paso-1, #paso-2, #paso-3 { display: none !important; }</style></head>', $htmlDesh1);
File::put("$dirEvidencia/primera-carga-deshacer-paso1.html", $htmlDesh1);

$alertaConfirmar = '<div role="alert" class="alumno-card" style="border-color:var(--color-warning); margin-bottom:1rem; background:color-mix(in srgb, var(--color-warning) 10%, transparent);">' .
                   '<strong>¿Deshacer la primera carga?</strong><p style="margin-top:4px;">Se borrarán los 35 alumnos y sus cuotas pendientes. Los usuarios y catálogos se conservan.</p></div>';
$htmlDesh2 = str_replace('<form method="POST" action="http://gestion-wings/sistema/primera-carga/deshacer"', $alertaConfirmar . '<form method="POST" action="http://gestion-wings/sistema/primera-carga/deshacer"', $htmlDesh1);
File::put("$dirEvidencia/primera-carga-deshacer-paso2.html", $htmlDesh2);

$pcEstado->update(['estado' => $pcEstadoOrig, 'detalle' => $pcDetalleOrig]);
$sessionStore->forget(['primera_carga_paso', 'primera_carga_revision']);

// ──────────────────────────────────────────────────────────────────────────
// 5. Validar o rechazar caja
// ──────────────────────────────────────────────────────────────────────────
$htmlCaja1 = $sanitizar($cajaCtrl->resumen($cajaCerrada->id)->render());
File::put("$dirEvidencia/validar-rechazar-caja-paso1.html", $htmlCaja1);

// Modal de rechazo abierto
$htmlCaja2 = str_replace('id="modal-rechazar" style="display:none;', 'id="modal-rechazar" style="display:flex;', $htmlCaja1);
File::put("$dirEvidencia/validar-rechazar-caja-paso2.html", $htmlCaja2);

$cajaValidada = clone $cajaCerrada;
$cajaValidada->estado = 'VALIDADA';
$cajaValidada->save();
$htmlVal = $sanitizar($cajaCtrl->resumen($cajaValidada->id)->render());
File::put("$dirEvidencia/validar-rechazar-caja-paso3.html", $htmlVal);
$cajaCerrada->update(['estado' => 'CERRADA']);

// ──────────────────────────────────────────────────────────────────────────
// 6. Auditar detalle y arqueo de caja
// ──────────────────────────────────────────────────────────────────────────
$htmlDet1 = $sanitizar($cajaCtrl->detalle($cajaCerrada->id)->render());
File::put("$dirEvidencia/auditar-detalle-caja-paso1.html", $htmlDet1);

$htmlDet2 = str_replace('</head>', '<style>.filtros-card.mb-4, .stats-bar.mb-3 { display: none !important; }</style></head>', $htmlDet1);
File::put("$dirEvidencia/auditar-detalle-caja-paso2.html", $htmlDet2);

// ──────────────────────────────────────────────────────────────────────────
// 7. Consultar Cashflow
// ──────────────────────────────────────────────────────────────────────────
$req1 = Request::create('/cashflow?periodo=mes&mes=10&anio=2026', 'GET');
$htmlCash1 = $sanitizar($cashflowCtrl->index($req1)->render());
File::put("$dirEvidencia/consultar-cashflow-paso1.html", $htmlCash1);

$req2 = Request::create('/cashflow?periodo=anio&anio=2026', 'GET');
$htmlCash2 = $sanitizar($cashflowCtrl->index($req2)->render());
File::put("$dirEvidencia/consultar-cashflow-paso2.html", $htmlCash2);

// ──────────────────────────────────────────────────────────────────────────
// 8. Registrar movimiento en Cashflow
// ──────────────────────────────────────────────────────────────────────────
$htmlMov1 = $sanitizar($cashflowCtrl->create()->render());
File::put("$dirEvidencia/registrar-movimiento-cashflow-paso1.html", $htmlMov1);

$htmlMov2 = str_replace('id="monto"', 'id="monto" value="15000"', $htmlMov1);
$htmlMov2 = str_replace('id="tipo-ingreso" checked', 'id="tipo-ingreso"', $htmlMov2);
$htmlMov2 = str_replace('id="tipo-egreso"', 'id="tipo-egreso" checked', $htmlMov2);
$htmlMov2 = str_replace('id="btn-ingreso"', 'id="btn-ingreso" style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; transition:all 0.15s;"', $htmlMov2);
$htmlMov2 = str_replace('id="btn-egreso"', 'id="btn-egreso" style="display:flex; align-items:center; justify-content:center; height:48px; border:2px solid; border-radius:var(--radius-btn); font-size:0.9rem; font-weight:700; background:var(--color-danger); color:#fff; border-color:var(--color-danger); transition:all 0.15s;"', $htmlMov2);
$htmlMov2 = str_replace('name="observaciones"></textarea>', 'name="observaciones">Compra de insumos deportivos para el torneo de patín</textarea>', $htmlMov2);
File::put("$dirEvidencia/registrar-movimiento-cashflow-paso2.html", $htmlMov2);

// ──────────────────────────────────────────────────────────────────────────
// 9. Transferir entre cuentas
// ──────────────────────────────────────────────────────────────────────────
$htmlTransf1 = $sanitizar($transfCtrl->create()->render());
File::put("$dirEvidencia/transferir-entre-cuentas-paso1.html", $htmlTransf1);

$htmlTransf2 = str_replace('id="monto"', 'id="monto" value="30000"', $htmlTransf1);
$htmlTransf2 = str_replace('name="observaciones"></textarea>', 'name="observaciones">Depósito de recaudación semanal en Banco Galicia</textarea>', $htmlTransf2);
$htmlTransf2 = preg_replace('/(<select[^>]*name="tipo_caja_destino_id"[^>]*>.*?<option value="\d+")/s', '$1 selected', $htmlTransf2);
File::put("$dirEvidencia/transferir-entre-cuentas-paso2.html", $htmlTransf2);

// ──────────────────────────────────────────────────────────────────────────
// 10. Dinero del dueño: retiros y aportes
// ──────────────────────────────────────────────────────────────────────────
$htmlDuenoCash = str_replace('name="monto" value=""', 'name="monto" value="50000"', $htmlMov1);
$htmlDuenoCash = str_replace('class="ds-filter-tab is-active" data-tipo="INGRESO"', 'class="ds-filter-tab" data-tipo="INGRESO"', $htmlDuenoCash);
$htmlDuenoCash = str_replace('class="ds-filter-tab " data-tipo="EGRESO"', 'class="ds-filter-tab is-active" data-tipo="EGRESO"', $htmlDuenoCash);
$htmlDuenoCash = str_replace('name="observaciones"></textarea>', 'name="observaciones">Retiro de utilidades del dueño</textarea>', $htmlDuenoCash);
File::put("$dirEvidencia/dinero-del-dueno-retiros-aportes-paso1.html", $htmlDuenoCash);

Auth::setUser($operativo);
$cajaAbiertaOp = CajaOperativa::where('usuario_operativo_id', $operativo->id)->where('estado', 'ABIERTA')->first();
if (!$cajaAbiertaOp) {
    $cajaAbiertaOp = \Tests\Support\CajaDeclarada::crear($operativo->id, $tipoEfectivo->id);
}
$htmlCajaMov = $sanitizar($cajaCtrl->movimientoForm()->render());
File::put("$dirEvidencia/dinero-del-dueno-retiros-aportes-paso2.html", $htmlCajaMov);
Auth::setUser($admin);

// ──────────────────────────────────────────────────────────────────────────
// 11. Generar liquidación mensual
// ──────────────────────────────────────────────────────────────────────────
$req = Request::create('/liquidaciones', 'GET');
$htmlLiqIndex = $sanitizar($liqCtrl->index($req)->render());
File::put("$dirEvidencia/generar-liquidacion-profesor-paso1.html", $htmlLiqIndex);

$req = Request::create('/liquidaciones/crear', 'GET');
$htmlLiqCreate = $sanitizar($liqCtrl->create($req)->render());
File::put("$dirEvidencia/generar-liquidacion-profesor-paso2.html", $htmlLiqCreate);

// ──────────────────────────────────────────────────────────────────────────
// 12. Revisar desglose de liquidación
// ──────────────────────────────────────────────────────────────────────────
$htmlLiqShow = $sanitizar($liqCtrl->show($liquidacionHora->id)->render());
File::put("$dirEvidencia/revisar-desglose-liquidacion-paso1.html", $htmlLiqShow);

$htmlLiqShow2 = str_replace('</head>', '<style>[data-liquidacion-actualizable="cabecera"] { display: none !important; }</style></head>', $htmlLiqShow);
File::put("$dirEvidencia/revisar-desglose-liquidacion-paso2.html", $htmlLiqShow2);

// ──────────────────────────────────────────────────────────────────────────
// 13. Recalcular o ajustar liquidación
// ──────────────────────────────────────────────────────────────────────────
$htmlRecalc1 = str_replace('</head>', '<style>.filtros-card { border: 2px solid var(--color-warning) !important; }</style></head>', $htmlLiqShow);
File::put("$dirEvidencia/recalcular-ajustar-liquidacion-paso1.html", $htmlRecalc1);

DB::table('liquidaciones')->where('id', $liquidacionHora->id)->update(['monto_final' => 115000]);
$htmlLiqAjustada = $sanitizar($liqCtrl->show($liquidacionHora->id)->render());
File::put("$dirEvidencia/recalcular-ajustar-liquidacion-paso2.html", $htmlLiqAjustada);
DB::table('liquidaciones')->where('id', $liquidacionHora->id)->update(['monto_final' => null]);

// ──────────────────────────────────────────────────────────────────────────
// 14. Cerrar y pagar liquidación
// ──────────────────────────────────────────────────────────────────────────
$htmlCerrar1 = str_replace('</head>', '<style>[data-liquidacion-actualizable="cabecera"] { border: 2px solid var(--color-btn-primary) !important; }</style></head>', $htmlLiqShow);
File::put("$dirEvidencia/cerrar-pagar-liquidacion-paso1.html", $htmlCerrar1);

// Estado CERRADA para mostrar panel de pago
DB::table('liquidaciones')->where('id', $liquidacionHora->id)->update(['estado' => 'CERRADA', 'estado_pago' => 'PENDIENTE']);
$liquidacionHora->refresh();
$htmlCerrada = $sanitizar($liqCtrl->show($liquidacionHora->id)->render());
File::put("$dirEvidencia/cerrar-pagar-liquidacion-paso2.html", $htmlCerrada);

// ──────────────────────────────────────────────────────────────────────────
// 15. Descargar recibo de liquidación
// ──────────────────────────────────────────────────────────────────────────
// Estado PAGADA para mostrar botón de recibo
DB::table('liquidaciones')->where('id', $liquidacionHora->id)->update([
    'estado' => 'CERRADA',
    'estado_pago' => 'PAGADA',
    'pagada_fecha' => Carbon::now(),
    'pagada_tipo_caja_id' => $tipoBanco->id,
    'pagada_subrubro_id' => $subrubroSueldoPatin->id,
]);
$liquidacionHora->refresh();
$htmlPagada = $sanitizar($liqCtrl->show($liquidacionHora->id)->render());
File::put("$dirEvidencia/descargar-recibo-liquidacion-paso1.html", $htmlPagada);

$reciboData = [
    'numero_recibo' => "LIQ-{$liquidacionHora->id}",
    'fecha_emision' => Carbon::now(),
    'fecha_pago' => Carbon::now(),
    'profesor' => [
        'nombre' => $profesorHora->apellido . ', ' . $profesorHora->nombre,
        'deporte' => $profesorHora->deporte->nombre ?? 'Patín Artístico',
        'tipo_liquidacion' => 'HORA',
        'modalidad_texto' => 'Por Hora ($ 4.500 / hora)',
    ],
    'periodo' => [
        'mes' => 10,
        'anio' => 2026,
        'nombre' => 'Octubre 2026',
        'texto' => 'Octubre 2026',
        'conteo_texto' => '1 clase dictada',
    ],
    'monto_total' => 108000,
    'total_liquidado' => 108000,
    'total_calculado' => 108000,
    'ajuste_final' => 0,
    'medio_pago' => [
        'tipo_caja' => 'Banco Galicia',
        'subrubro' => 'Sueldo - Méndez, Lucía',
    ],
    'observaciones' => null,
    'modalidad' => 'HORA',
    'desglose_por_cuota' => false,
    'detalles' => [
        [
            'fecha' => '05/10/2026',
            'grupo' => 'Patín Escuela',
            'minutos' => 60,
            'horas' => 1.0,
            'estado' => 'Dictada',
            'subtotal' => 4500,
        ]
    ],
];
$htmlRecibo = $sanitizar(View::make('pdfs.recibo-liquidacion', $reciboData)->render());
File::put("$dirEvidencia/descargar-recibo-liquidacion-paso2.html", $htmlRecibo);

// Restaurar liquidación
DB::table('liquidaciones')->where('id', $liquidacionHora->id)->update([
    'estado' => 'ABIERTA',
    'estado_pago' => 'PENDIENTE',
    'pagada_fecha' => null,
    'pagada_tipo_caja_id' => null,
    'pagada_subrubro_id' => null,
]);

// ──────────────────────────────────────────────────────────────────────────
// T13: Signo de pregunta [?] en pantallas del dueño
// ──────────────────────────────────────────────────────────────────────────
$t13Vistas = [
    'primera-carga' => "$dirEvidencia/primera-carga-descargar-plantilla-paso1.html",
    'caja-resumen' => "$dirEvidencia/validar-rechazar-caja-paso1.html",
    'caja-detalle-admin' => "$dirEvidencia/auditar-detalle-caja-paso1.html",
    'cashflow' => "$dirEvidencia/consultar-cashflow-paso1.html",
    'cashflow-movimiento' => "$dirEvidencia/registrar-movimiento-cashflow-paso1.html",
    'cashflow-transferencia' => "$dirEvidencia/transferir-entre-cuentas-paso1.html",
    'liquidaciones' => "$dirEvidencia/generar-liquidacion-profesor-paso1.html",
    'liquidaciones-crear' => "$dirEvidencia/generar-liquidacion-profesor-paso2.html",
    'liquidaciones-show' => "$dirEvidencia/revisar-desglose-liquidacion-paso1.html",
];

foreach ($t13Vistas as $slug => $fuenteHtml) {
    $contenido = File::get($fuenteHtml);
    File::put("$dirEvidencia/signo-pregunta-{$slug}-1366.html", $contenido);
    File::put("$dirEvidencia/signo-pregunta-{$slug}-marco-375.html", $armarMarco375("signo-pregunta-{$slug}-1366.html"));
}

echo "[OK] 31 archivos de pasos y 18 archivos T13 generados con éxito en docs/06-pruebas/PRU-04/evidencia-t12-t13/.\n";
