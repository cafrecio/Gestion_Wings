<?php

putenv('DB_DATABASE=wings_manual_gemini');
$_ENV['DB_DATABASE'] = 'wings_manual_gemini';

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\Asistencia;
use App\Models\CajaOperativa;
use App\Models\CashflowMovimiento;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\DeudaCuota;
use App\Models\Grupo;
use App\Models\GrupoPlan;
use App\Models\MovimientoOperativo;
use App\Models\Nivel;
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

Carbon::setTestNow('2026-10-29 17:00:00');

$dirEvidencia = base_path('docs/06-pruebas/PRU-04/evidencia-t12-t13');
File::ensureDirectoryExists($dirEvidencia);

$dirImgOperativo = resource_path('manual/img/operativo');
File::ensureDirectoryExists($dirImgOperativo);

// Usuarios
$admin = User::firstOrNew(['email' => 'admin@wings.test']);
$admin->name = 'Carlos Freccero';
$admin->rol = User::ROL_ADMIN;
$admin->activo = true;
$admin->password = bcrypt('password');
$admin->save();

$operativo = User::firstOrNew(['email' => 'operativa@wings.test']);
$operativo->name = 'Sandra Operativa';
$operativo->rol = User::ROL_OPERATIVO;
$operativo->activo = true;
$operativo->password = bcrypt('password');
$operativo->save();

Auth::setUser($operativo);
View::share('errors', new \Illuminate\Support\ViewErrorBag);

// Catálogos
$deportePatin = Deporte::firstOrCreate(['nombre' => 'Patín Artístico'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);
$deporteFutbol = Deporte::firstOrCreate(['nombre' => 'Fútbol Infantil'], ['tipo_liquidacion' => 'HORA', 'activo' => true]);

$nivelInicial = Nivel::firstOrCreate(['nombre' => 'Inicial']);
$nivelAvanzado = Nivel::firstOrCreate(['nombre' => 'Avanzado']);

$grupoPatin = Grupo::firstOrCreate(['deporte_id' => $deportePatin->id, 'nivel_id' => $nivelInicial->id], ['nombre' => 'Patín Escuela', 'activo' => true]);
$grupoFutbol = Grupo::firstOrCreate(['deporte_id' => $deporteFutbol->id, 'nivel_id' => $nivelInicial->id], ['nombre' => 'Fútbol Inicial', 'activo' => true]);

$planPatin2 = GrupoPlan::firstOrCreate(['grupo_id' => $grupoPatin->id, 'clases_por_semana' => 2], ['precio_mensual' => 25000, 'activo' => true]);
$planPatin3 = GrupoPlan::firstOrCreate(['grupo_id' => $grupoPatin->id, 'clases_por_semana' => 3], ['precio_mensual' => 32000, 'activo' => true]);
$planFutbol = GrupoPlan::firstOrCreate(['grupo_id' => $grupoFutbol->id, 'clases_por_semana' => 2], ['precio_mensual' => 22000, 'activo' => true]);

$tipoCajaEfectivo = TipoCaja::firstOrCreate(['nombre' => 'Efectivo'], ['activo' => true]);
DB::table('caja_mostrador')->updateOrInsert(['id' => 1], ['tipo_caja_id' => $tipoCajaEfectivo->id]);

$rubroCuotas = Rubro::firstOrCreate(['nombre' => 'Cuotas'], ['tipo' => 'INGRESO', 'observacion' => '']);
$subrubroCuotas = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroCuotas->id, 'nombre' => 'Cuota Mensual'],
    ['permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => true]
);

$rubroVentas = Rubro::firstOrCreate(['nombre' => 'Indumentaria y Venta'], ['tipo' => 'INGRESO', 'observacion' => '']);
$subrubroVentas = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroVentas->id, 'nombre' => 'Venta de indumentaria'],
    ['permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => false]
);

$rubroGastos = Rubro::firstOrCreate(['nombre' => 'Gastos Menores'], ['tipo' => 'EGRESO', 'observacion' => '']);
$subrubroGastos = Subrubro::firstOrCreate(
    ['rubro_id' => $rubroGastos->id, 'nombre' => 'Artículos de limpieza e insumos'],
    ['permitido_para' => User::ROL_OPERATIVO, 'afecta_caja' => true, 'es_reservado_sistema' => false]
);

// Alumnos
$martina = Alumno::firstOrCreate(
    ['dni' => '44888999'],
    [
        'nombre'           => 'Martina',
        'apellido'         => 'Gómez',
        'fecha_nacimiento' => '2014-06-12',
        'celular'          => '3415998877',
        'deporte_id'       => $deportePatin->id,
        'grupo_id'         => $grupoPatin->id,
        'fecha_alta'       => '2026-08-01',
        'activo'           => true,
        'nombre_tutor'     => 'Laura Gómez',
        'telefono_tutor'   => '3415998877',
    ]
);
AlumnoPlan::firstOrCreate(['alumno_id' => $martina->id, 'plan_id' => $planPatin2->id], ['fecha_desde' => '2026-08-01', 'activo' => true]);
DeudaCuota::firstOrCreate(
    ['alumno_id' => $martina->id, 'periodo' => '2026-10'],
    ['monto_original' => 25000, 'saldo_pendiente' => 25000, 'fecha_vencimiento' => '2026-10-10', 'estado' => 'PENDIENTE']
);

$lucas = Alumno::firstOrCreate(
    ['dni' => '45112233'],
    [
        'nombre'           => 'Lucas',
        'apellido'         => 'Benítez',
        'fecha_nacimiento' => '2015-03-20',
        'celular'          => '3416112233',
        'deporte_id'       => $deporteFutbol->id,
        'grupo_id'         => $grupoFutbol->id,
        'fecha_alta'       => '2026-09-01',
        'activo'           => true,
        'nombre_tutor'     => 'Carlos Benítez',
        'telefono_tutor'   => '3416112233',
    ]
);
AlumnoPlan::firstOrCreate(['alumno_id' => $lucas->id, 'plan_id' => $planFutbol->id], ['fecha_desde' => '2026-09-01', 'activo' => true]);

// Alumno para inactivo
$sofia = Alumno::firstOrCreate(
    ['dni' => '46223344'],
    [
        'nombre'           => 'Sofía',
        'apellido'         => 'Fernández',
        'fecha_nacimiento' => '2016-08-15',
        'celular'          => '3417223344',
        'deporte_id'       => $deportePatin->id,
        'grupo_id'         => $grupoPatin->id,
        'fecha_alta'       => '2026-07-01',
        'activo'           => true,
    ]
);

// Caja del operativo
MovimientoOperativo::whereIn('caja_operativa_id', CajaOperativa::where('usuario_operativo_id', $operativo->id)->pluck('id'))->delete();
CajaOperativa::where('usuario_operativo_id', $operativo->id)->delete();

$caja = \Tests\Support\CajaDeclarada::crear($operativo->id, $tipoCajaEfectivo->id);
$caja->update([
    'efectivo_inicial' => 15000,
    'estado'           => 'ABIERTA',
    'apertura_at'      => Carbon::now(),
]);

// Movimientos de prueba en la caja
$movIngreso = MovimientoOperativo::firstOrCreate(
    ['caja_operativa_id' => $caja->id, 'observaciones' => 'Venta remera entrenamiento talle 12'],
    [
        'tipo_caja_id'       => $tipoCajaEfectivo->id,
        'subrubro_id'        => $subrubroVentas->id,
        'monto'              => 8500,
        'usuario_id'         => $operativo->id,
        'fecha'              => Carbon::now()->subHours(3),
        'estado'             => MovimientoOperativo::ESTADO_ACTIVO,
        'created_at'         => Carbon::now()->subHours(3),
    ]
);

$movGasto = MovimientoOperativo::firstOrCreate(
    ['caja_operativa_id' => $caja->id, 'observaciones' => 'Compra artículos de limpieza para vestuarios'],
    [
        'tipo_caja_id'       => $tipoCajaEfectivo->id,
        'subrubro_id'        => $subrubroGastos->id,
        'monto'              => 3200,
        'usuario_id'         => $operativo->id,
        'fecha'              => Carbon::now()->subHours(2),
        'estado'             => MovimientoOperativo::ESTADO_ACTIVO,
        'created_at'         => Carbon::now()->subHours(2),
    ]
);

// Profesor y clases
$profeLucia = Profesor::firstOrCreate(
    ['dni' => '32111222'],
    [
        'nombre'           => 'Lucía',
        'apellido'         => 'Méndez',
        'deporte_id'       => $deportePatin->id,
        'fecha_nacimiento' => '1992-05-14',
        'direccion'        => 'Av. Corrientes 1234',
        'localidad'        => 'Rosario',
        'activo'           => true,
        'telefono'         => '3415556677',
    ]
);

$claseHoy = Clase::firstOrCreate(
    ['fecha' => '2026-10-29', 'hora_inicio' => '17:00:00', 'grupo_id' => $grupoPatin->id],
    [
        'hora_fin'                 => '18:30:00',
        'deporte_id'               => $deportePatin->id,
        'cancelada'                => false,
        'validada_para_liquidacion'=> false,
    ]
);
if (!$claseHoy->profesores()->where('profesores.id', $profeLucia->id)->exists()) {
    $claseHoy->profesores()->attach($profeLucia->id);
}
Asistencia::firstOrCreate(
    ['clase_id' => $claseHoy->id, 'alumno_id' => $martina->id],
    ['presente' => true, 'fecha_hora_marca' => Carbon::now()]
);

// Revisión de cobranza de prueba
AlumnoRevisionCobranza::firstOrCreate(
    ['alumno_id' => $sofia->id, 'periodo_objetivo' => '2026-10'],
    [
        'estado_revision' => 'PENDIENTE',
        'motivo'          => 'Sin asistencias registradas en el mes anterior',
    ]
);

$publicBuild = 'file:///' . str_replace('\\', '/', public_path('build')) . '/';
$publicImg = 'file:///' . str_replace('\\', '/', public_path('img')) . '/';
$manualImgOperativo = 'file:///' . str_replace('\\', '/', resource_path('manual/img/operativo')) . '/';
$manualImgAdmin = 'file:///' . str_replace('\\', '/', resource_path('manual/img/admin')) . '/';

$sanitizar = function (string $html) use ($publicBuild, $publicImg, $manualImgOperativo, $manualImgAdmin): string {
    $html = preg_replace('#https?://[^/]+/build/#', $publicBuild, $html);
    $html = preg_replace('#https?://[^/]+/img/#', $publicImg, $html);
    $html = str_replace(['src="/build/', 'href="/build/'], ['src="' . $publicBuild, 'href="' . $publicBuild], $html);
    $html = str_replace(['src="/img/', 'href="/img/'], ['src="' . $publicImg, 'href="' . $publicImg], $html);
    $html = str_replace('/ayuda/img/operativo/', $manualImgOperativo, $html);
    $html = str_replace('/ayuda/img/admin/', $manualImgAdmin, $html);
    return $html;
};

$armarMarco375 = function (string $archivoHtml): string {
    return '<!doctype html><meta charset="utf-8">' . "\n" .
           '<body style="margin:0;background:#0f172a;display:flex;justify-content:center;align-items:flex-start;padding:24px 0;">' . "\n" .
           '<iframe src="' . $archivoHtml . '" style="width:375px;height:950px;border:1px solid #334155;border-radius:14px;background:#fff;box-shadow:0 12px 30px rgba(0,0,0,0.6);"></iframe></body>' . "\n";
};

$cajaCtrl = app(\App\Http\Controllers\CajaWebController::class);
$alumnoCtrl = app(\App\Http\Controllers\AlumnoWebController::class);
$cobranzaCtrl = app(\App\Http\Controllers\CobranzaWebController::class);
$movCtrl = app(\App\Http\Controllers\MovimientoWebController::class);
$claseCtrl = app(\App\Http\Controllers\ClaseWebController::class);
$revCtrl = app(\App\Http\Controllers\RevisionCobranzaWebController::class);
$grupoCtrl = app(\App\Http\Controllers\GrupoWebController::class);
$ayudaCtrl = app(\App\Http\Controllers\AyudaWebController::class);

echo "Renderizando vistas reales de Wings para Etapa A...\n";

Auth::setUser($operativo);
$sessionStore = session()->driver();

$crearRequest = function (string $uri, string $method = 'GET', array $params = [], ?User $user = null) use ($operativo, $sessionStore): Request {
    $req = Request::create($uri, $method, $params);
    $u = $user ?: $operativo;
    $req->setUserResolver(fn() => $u);
    $req->setLaravelSession($sessionStore);
    app()->instance('request', $req);
    return $req;
};

// ──────────────────────────────────────────────────────────────────────────
// 1. Buscador de Ayuda (T13)
// ──────────────────────────────────────────────────────────────────────────
$htmlAyudaIndex = $sanitizar($ayudaCtrl->index($crearRequest('/ayuda', 'GET', ['q' => 'cobrar']))->render());
file_put_contents($dirEvidencia . '/ayuda-buscador-1366.html', $htmlAyudaIndex);
file_put_contents($dirEvidencia . '/ayuda-buscador-marco-375.html', $armarMarco375('ayuda-buscador-1366.html'));

// ──────────────────────────────────────────────────────────────────────────
// 2. cobrar-pago-parcial
// ──────────────────────────────────────────────────────────────────────────
// Paso 1: selector de cobro
$htmlCobrarPaso1 = $sanitizar($cajaCtrl->cobrarCuotaSelect($crearRequest('/caja/cobrar', 'GET', ['search' => 'Gómez']))->render());
file_put_contents($dirEvidencia . '/cobrar-pago-parcial-paso1.html', $htmlCobrarPaso1);

// Paso 2: ficha con monto parcial editado a $10.000
$htmlCobrarBase = $cajaCtrl->cobrar($martina->id)->render();
$htmlCobrarParcial2 = preg_replace('/(name="periodos\[\]"\s+value="2026-10"[^>]*)/', '$1 checked="checked"', $htmlCobrarBase);
$htmlCobrarParcial2 = preg_replace('/(name="montos_cuota\[2026-10\]"[^>]*value=")[^"]*(")/', '$110000$2', $htmlCobrarParcial2);
$htmlCobrarParcial2 = str_replace('display:none;" id="resumen-total"', 'display:block;" id="resumen-total"', $htmlCobrarParcial2);
$htmlCobrarParcial2 = preg_replace('/(<span id="total-label"[^>]*>)[^<]*(<\/span>)/', '$1$ 10.000$2', $htmlCobrarParcial2);
file_put_contents($dirEvidencia . '/cobrar-pago-parcial-paso2.html', $sanitizar($htmlCobrarParcial2));

// Paso 3: Recibo oficial parcial emitido ($10.000)
$datosReciboParcial = [
    'numero_recibo'      => 'CUOTA-1043',
    'fecha_emision'      => Carbon::now(),
    'fecha_pago'         => Carbon::now()->toDateString(),
    'anulado'            => false,
    'fecha_cancelacion'  => null,
    'cancelado_por'      => null,
    'motivo_cancelacion' => null,
    'alumno'             => ['nombre' => 'Martina Gómez', 'dni' => '44.888.999', 'deporte' => 'Patín Artístico'],
    'periodos'           => [['periodo' => '2026-10', 'periodo_texto' => 'Cuota Octubre 2026 (Pago parcial)', 'monto_aplicado' => 10000]],
    'monto_total'        => 10000,
    'medio_cobro'        => ['tipo_caja' => 'Efectivo', 'origen' => 'Operativo'],
    'observaciones'      => 'Entrega a cuenta cuota octubre - Saldo restante $ 15.000',
];
$htmlReciboP3 = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Recibo CUOTA-1043 – Wings</title>' .
    '<style>body{background:#0f172a;margin:0;padding:24px 0;display:flex;justify-content:center;font-family:\'DejaVu Sans\',Arial,sans-serif;}' .
    '.recibo-marco{background:#fff;width:560px;padding:24px;border-radius:8px;box-shadow:0 12px 30px rgba(0,0,0,0.5);}</style></head>' .
    '<body><div class="recibo-marco">' . view('pdfs.recibo-cuota', $datosReciboParcial)->render() . '</div></body></html>';
file_put_contents($dirEvidencia . '/cobrar-pago-parcial-paso3.html', $htmlReciboP3);

// ──────────────────────────────────────────────────────────────────────────
// 3. buscar-alumnos
// ──────────────────────────────────────────────────────────────────────────
$cobranzaService = app(\App\Services\CobranzaEstadoService::class);
$htmlAlumnosIndex = $sanitizar($alumnoCtrl->index($crearRequest('/alumnos', 'GET'), $cobranzaService)->render());
file_put_contents($dirEvidencia . '/buscar-alumnos-paso1.html', $htmlAlumnosIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-alumnos-1366.html', $htmlAlumnosIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-alumnos-marco-375.html', $armarMarco375('signo-pregunta-alumnos-1366.html'));

$htmlAlumnosPaso2 = $sanitizar($alumnoCtrl->index($crearRequest('/alumnos', 'GET', ['search' => 'Gomez']), $cobranzaService)->render());
file_put_contents($dirEvidencia . '/buscar-alumnos-paso2.html', $htmlAlumnosPaso2);

$htmlAlumnosPaso3 = $sanitizar($alumnoCtrl->index($crearRequest('/alumnos', 'GET', ['deporte_id' => $deportePatin->id, 'grupo_id' => $grupoPatin->id]), $cobranzaService)->render());
file_put_contents($dirEvidencia . '/buscar-alumnos-paso3.html', $htmlAlumnosPaso3);

// ──────────────────────────────────────────────────────────────────────────
// 4. alta-alumno
// ──────────────────────────────────────────────────────────────────────────
$htmlAltaP1 = preg_replace(
    '/(<a[^>]*href="[^"]*\/alumnos\/create"[^>]*>[\s\r\n]*Nuevo[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlAlumnosIndex
);
file_put_contents($dirEvidencia . '/alta-alumno-paso1.html', $htmlAltaP1);

$crearRequest('/alumnos/create', 'GET');
$sessionStore->forget('_old_input');
$htmlAltaP2 = $sanitizar($alumnoCtrl->create()->render());
file_put_contents($dirEvidencia . '/alta-alumno-paso2.html', $htmlAltaP2);

$sessionStore->put('_old_input', [
    'nombre' => 'Valentina',
    'apellido' => 'Ríos',
    'dni' => '47888111',
    'fecha_nacimiento' => '2016-04-10',
    'fecha_alta' => '2026-10-29',
    'celular' => '3419998877',
    'deporte_id' => $deportePatin->id,
    'grupo_id' => $grupoPatin->id,
    'nombre_tutor' => 'Mariana Ríos',
    'telefono_tutor' => '3419998877',
]);
$htmlAltaP3 = $sanitizar($alumnoCtrl->create()->render());
file_put_contents($dirEvidencia . '/alta-alumno-paso3.html', $htmlAltaP3);
$sessionStore->forget('_old_input');

// ──────────────────────────────────────────────────────────────────────────
// 5. modificar-alumno
// ──────────────────────────────────────────────────────────────────────────
$htmlModificarP1 = preg_replace(
    '/(<a[^>]*href="[^"]*\/alumnos\/[0-9]+\/edit"[^>]*>[\s\r\n]*Editar[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlAlumnosIndex,
    1
);
file_put_contents($dirEvidencia . '/modificar-alumno-paso1.html', $htmlModificarP1);

$htmlEditAlumno = $sanitizar($alumnoCtrl->edit($martina->id)->render());
$htmlEditAlumnoP2 = str_replace(
    'id="celular"',
    'id="celular" style="outline:3px solid var(--color-warning); outline-offset:2px;"',
    $htmlEditAlumno
);
file_put_contents($dirEvidencia . '/modificar-alumno-paso2.html', $sanitizar($htmlEditAlumnoP2));

$htmlShowMartina = $sanitizar($alumnoCtrl->show($martina->id, $cobranzaService)->render());
$htmlShowMartinaModificado = preg_replace(
    '/(<div class="ds-topbar">)/',
    '$1<div class="ds-flash ds-flash--success mb-3">Alumno actualizado con éxito.</div>',
    $htmlShowMartina
);
file_put_contents($dirEvidencia . '/modificar-alumno-paso3.html', $sanitizar($htmlShowMartinaModificado));

// ──────────────────────────────────────────────────────────────────────────
// 6. cambiar-grupo-alumno
// ──────────────────────────────────────────────────────────────────────────
$htmlCambioGrupoP1 = str_replace(
    'id="grupo_id"',
    'id="grupo_id" style="outline:3px solid var(--color-warning); outline-offset:2px;"',
    $htmlEditAlumno
);
file_put_contents($dirEvidencia . '/cambiar-grupo-alumno-paso1.html', $sanitizar($htmlCambioGrupoP1));

$htmlCambioGrupoP2 = str_replace(
    '<option value="' . $planPatin2->id . '"',
    '<option value="' . $planPatin3->id . '" selected="selected"',
    $htmlEditAlumno
);
file_put_contents($dirEvidencia . '/cambiar-grupo-alumno-paso2.html', $sanitizar($htmlCambioGrupoP2));

$htmlShowMartinaPlanCambiado = preg_replace(
    '/(<div[^>]*class="[^"]*alumno-card[^"]*"[^>]*>)/',
    '$1<div style="outline:3px solid var(--color-brand); outline-offset:2px; padding:4px; border-radius:6px;">',
    $htmlShowMartina
);
file_put_contents($dirEvidencia . '/cambiar-grupo-alumno-paso3.html', $sanitizar($htmlShowMartinaPlanCambiado));

// ──────────────────────────────────────────────────────────────────────────
// 7. pasar-alumno-inactivo
// ──────────────────────────────────────────────────────────────────────────
$htmlInactivoP1 = str_replace(
    'class="ds-toggle',
    'style="outline:3px solid var(--color-danger); outline-offset:3px; border-radius:14px;" class="ds-toggle',
    $htmlShowMartina
);
file_put_contents($dirEvidencia . '/pasar-alumno-inactivo-paso1.html', $sanitizar($htmlInactivoP1));

$htmlInactivoP2 = str_replace('checked="checked"', '', $htmlShowMartina);
$htmlInactivoP2 = str_replace('data-checked="1"', 'data-checked="0"', $htmlInactivoP2);
$htmlInactivoP2 = preg_replace('/(<div class="ds-topbar">)/', '$1<div class="ds-flash ds-flash--success mb-3">Alumno desactivado correctamente.</div>', $htmlInactivoP2);
file_put_contents($dirEvidencia . '/pasar-alumno-inactivo-paso2.html', $sanitizar($htmlInactivoP2));

// ──────────────────────────────────────────────────────────────────────────
// 8. consultar-cobranza
// ──────────────────────────────────────────────────────────────────────────
$htmlCobranzaIndex = $sanitizar($cobranzaCtrl->index($crearRequest('/cobranza', 'GET'))->render());
file_put_contents($dirEvidencia . '/consultar-cobranza-paso1.html', $htmlCobranzaIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-cobranza-1366.html', $htmlCobranzaIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-cobranza-marco-375.html', $armarMarco375('signo-pregunta-cobranza-1366.html'));

$htmlCobranzaP2 = $sanitizar($cobranzaCtrl->index($crearRequest('/cobranza', 'GET', ['deporte_id' => $deporteFutbol->id]))->render());
file_put_contents($dirEvidencia . '/consultar-cobranza-paso2.html', $htmlCobranzaP2);

$htmlCobranzaP3 = preg_replace(
    '/(<a[^>]*href="[^"]*\/caja\/cobrar\/[^"]*"[^>]*>[\s\r\n]*Cobrar[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlCobranzaIndex
);
file_put_contents($dirEvidencia . '/consultar-cobranza-paso3.html', $htmlCobranzaP3);

// ──────────────────────────────────────────────────────────────────────────
// 9. consultar-movimientos
// ──────────────────────────────────────────────────────────────────────────
$htmlMovIndex = $sanitizar($movCtrl->index($crearRequest('/movimientos', 'GET'))->render());
file_put_contents($dirEvidencia . '/consultar-movimientos-paso1.html', $htmlMovIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-movimientos-1366.html', $htmlMovIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-movimientos-marco-375.html', $armarMarco375('signo-pregunta-movimientos-1366.html'));

$htmlMovP2 = $sanitizar($movCtrl->index($crearRequest('/movimientos', 'GET', ['tipo' => 'INGRESO', 'desde' => '2026-10-01', 'hasta' => '2026-10-29']))->render());
file_put_contents($dirEvidencia . '/consultar-movimientos-paso2.html', $htmlMovP2);

$htmlMovP3 = $sanitizar($movCtrl->index($crearRequest('/movimientos', 'GET', ['tipo' => 'EGRESO']))->render());
file_put_contents($dirEvidencia . '/consultar-movimientos-paso3.html', $htmlMovP3);

// ──────────────────────────────────────────────────────────────────────────
// 10. abrir-caja
// ──────────────────────────────────────────────────────────────────────────
CajaOperativa::where('estado', 'ABIERTA')->update(['estado' => 'CERRADA']);
$sessionStore->forget('_old_input');
$htmlApertura = $sanitizar($cajaCtrl->apertura($crearRequest('/caja/apertura', 'GET'))->render());
file_put_contents($dirEvidencia . '/abrir-caja-paso1.html', $htmlApertura);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-apertura-1366.html', $htmlApertura);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-apertura-marco-375.html', $armarMarco375('signo-pregunta-caja-apertura-1366.html'));

$sessionStore->put('_old_input', [
    'efectivo_inicial' => '15000',
    'confirmacion' => '1',
]);
$htmlAperturaP2 = $sanitizar($cajaCtrl->apertura($crearRequest('/caja/apertura', 'GET'))->render());
$htmlAperturaP2 = preg_replace('/(<button[^>]*type="submit"[^>]*>[\s\r\n]*Abrir[\s\r\n]*<\/button>)/s', '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>', $htmlAperturaP2);
file_put_contents($dirEvidencia . '/abrir-caja-paso2.html', $htmlAperturaP2);
$sessionStore->forget('_old_input');
CajaOperativa::where('id', $caja->id)->update(['estado' => 'ABIERTA']);
$caja->refresh();

// ──────────────────────────────────────────────────────────────────────────
// 11. registrar-ingreso-caja
// ──────────────────────────────────────────────────────────────────────────
$htmlCajaIndex = $sanitizar($cajaCtrl->index($crearRequest('/caja', 'GET'))->render());
$htmlCajaIndexIngreso = preg_replace(
    '/(<a[^>]*href="[^"]*\/caja\/' . $caja->id . '\/editar"[^>]*>[\s\r\n]*Agregar[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlCajaIndex
);
file_put_contents($dirEvidencia . '/registrar-ingreso-caja-paso1.html', $htmlCajaIndexIngreso);

$sessionStore->put('_old_input', ['rubro_id' => $rubroVentas->id]);
$htmlMovFormIngreso = $sanitizar($cajaCtrl->movimientoForm()->render());
file_put_contents($dirEvidencia . '/registrar-ingreso-caja-paso2.html', $htmlMovFormIngreso);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-movimiento-1366.html', $htmlMovFormIngreso);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-movimiento-marco-375.html', $armarMarco375('signo-pregunta-caja-movimiento-1366.html'));
$sessionStore->forget('_old_input');

session()->put('_old_input', [
    'tipo_ie' => 'INGRESO',
    'tipo_caja_id' => $tipoCajaEfectivo->id,
    'rubro_id' => $rubroVentas->id,
    'subrubro_id' => $subrubroVentas->id,
    'monto' => '8500',
    'observaciones' => 'Venta remera entrenamiento talle 12',
]);
$htmlMovFormIngresoP3 = $sanitizar($cajaCtrl->movimientoForm()->render());
file_put_contents($dirEvidencia . '/registrar-ingreso-caja-paso3.html', $htmlMovFormIngresoP3);
session()->forget('_old_input');

// ──────────────────────────────────────────────────────────────────────────
// 12. registrar-gasto-caja
// ──────────────────────────────────────────────────────────────────────────
$sessionStore->put('_old_input', ['rubro_id' => $rubroGastos->id]);
$htmlMovFormEgresoP1 = $sanitizar($cajaCtrl->movimientoForm()->render());
file_put_contents($dirEvidencia . '/registrar-gasto-caja-paso1.html', $htmlMovFormEgresoP1);
$sessionStore->forget('_old_input');

$sessionStore->put('_old_input', [
    'tipo_ie' => 'EGRESO',
    'tipo_caja_id' => $tipoCajaEfectivo->id,
    'rubro_id' => $rubroGastos->id,
    'subrubro_id' => $subrubroGastos->id,
    'monto' => '3200',
    'observaciones' => 'Compra artículos de limpieza para vestuarios',
]);
$htmlMovFormEgresoP2 = $sanitizar($cajaCtrl->movimientoForm()->render());
file_put_contents($dirEvidencia . '/registrar-gasto-caja-paso2.html', $htmlMovFormEgresoP2);
$sessionStore->forget('_old_input');

// Detalle con el egreso impactado
MovimientoOperativo::firstOrCreate(
    ['caja_operativa_id' => $caja->id, 'observaciones' => 'Compra artículos de limpieza para vestuarios'],
    [
        'tipo_caja_id' => $tipoCajaEfectivo->id,
        'subrubro_id'  => $subrubroGastos->id,
        'monto'        => -3200,
        'usuario_id'   => $operativo->id,
        'fecha'        => Carbon::now()->subMinutes(15),
    ]
);
$htmlDetalleConGasto = $sanitizar($cajaCtrl->detalle($caja->id)->render());
$htmlDetalleConGastoP3 = preg_replace(
    '/(<div class="ds-topbar">)/',
    '$1<div class="ds-flash ds-flash--success mb-3">Movimiento de egreso registrado con éxito.</div>',
    $htmlDetalleConGasto
);
$htmlDetalleConGastoP3 = str_replace(
    'Compra artículos de limpieza para vestuarios',
    '<span style="outline:2px solid var(--color-success); outline-offset:3px; border-radius:4px;">Compra artículos de limpieza para vestuarios</span>',
    $htmlDetalleConGastoP3
);
file_put_contents($dirEvidencia . '/registrar-gasto-caja-paso3.html', $htmlDetalleConGastoP3);

// ──────────────────────────────────────────────────────────────────────────
// 13. cancelar-movimiento-caja
// ──────────────────────────────────────────────────────────────────────────
$htmlCajaIndexDetalle = preg_replace(
    '/(<a[^>]*href="[^"]*\/caja\/' . $caja->id . '\/detalle"[^>]*>[\s\r\n]*Detalle[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlCajaIndex
);
file_put_contents($dirEvidencia . '/cancelar-movimiento-caja-paso1.html', $htmlCajaIndexDetalle);

$htmlDetalleCaja = $sanitizar($cajaCtrl->detalle($caja->id)->render());
$htmlDetalleCancelarP2 = str_replace(
    'data-abrir-cancelar',
    'style="outline:3px solid var(--color-danger); outline-offset:2px; border-radius:4px;" data-abrir-cancelar',
    $htmlDetalleCaja
);
file_put_contents($dirEvidencia . '/cancelar-movimiento-caja-paso2.html', $htmlDetalleCancelarP2);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-detalle-1366.html', $htmlDetalleCaja);
file_put_contents($dirEvidencia . '/signo-pregunta-caja-detalle-marco-375.html', $armarMarco375('signo-pregunta-caja-detalle-1366.html'));

$htmlDetalleModal = str_replace('id="modal-cancelar" style="display:none;', 'id="modal-cancelar" style="display:flex;', $htmlDetalleCaja);
$htmlDetalleModal = str_replace('placeholder="Motivo de la cancelación..."', 'placeholder="Motivo de la cancelación..." value="Monto ingresado incorrectamente"', $htmlDetalleModal);
file_put_contents($dirEvidencia . '/cancelar-movimiento-caja-paso3.html', $htmlDetalleModal);

// ──────────────────────────────────────────────────────────────────────────
// 14. cerrar-caja
// ──────────────────────────────────────────────────────────────────────────
$htmlCierre = $sanitizar($cajaCtrl->cierre($caja->id)->render());
file_put_contents($dirEvidencia . '/cerrar-caja-paso1.html', $htmlCierre);

$htmlCierreP2 = preg_replace('/(id="efectivo_contado"[^>]*value=")[^"]*(")/', '$120300$2', $htmlCierre);
$htmlCierreP2 = preg_replace('/(id="cambio_retenido"[^>]*value=")[^"]*(")/', '$115000$2', $htmlCierreP2);
$htmlCierreP2 = str_replace('Ingresá el efectivo contado', '$ 0 (Exacto)', $htmlCierreP2);
$htmlCierreP2 = str_replace('Contado menos cambio que queda', '$ 5.300', $htmlCierreP2);
file_put_contents($dirEvidencia . '/cerrar-caja-paso2.html', $sanitizar($htmlCierreP2));

$htmlCierreP3 = preg_replace(
    '/(<button[^>]*type="submit"[^>]*>[\s\r\n]*Cerrar[\s\r\n]*<\/button>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlCierreP2
);
file_put_contents($dirEvidencia . '/cerrar-caja-paso3.html', $sanitizar($htmlCierreP3));

// ──────────────────────────────────────────────────────────────────────────
// 15. rectificar-caja
// ──────────────────────────────────────────────────────────────────────────
$htmlRectificar = $sanitizar($cajaCtrl->rectificar($caja->id)->render());
file_put_contents($dirEvidencia . '/rectificar-caja-paso1.html', $htmlRectificar);

$htmlRectificarP2 = preg_replace('/(id="motivo_rectificacion"[^>]*value=")[^"]*(")/', '$1Se tipeó un cero de más en la apertura$2', $htmlRectificar);
file_put_contents($dirEvidencia . '/rectificar-caja-paso2.html', $sanitizar($htmlRectificarP2));

// ──────────────────────────────────────────────────────────────────────────
// 16. consultar-historial-cajas
// ──────────────────────────────────────────────────────────────────────────
$htmlCajaHistorialP1 = preg_replace(
    '/(<a[^>]*href="[^"]*\/caja\/historial"[^>]*>[\s\r\n]*Historial[\s\r\n]*<\/a>)/s',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlCajaIndex
);
file_put_contents($dirEvidencia . '/consultar-historial-cajas-paso1.html', $htmlCajaHistorialP1);

$htmlHistorialFiltro = $sanitizar($cajaCtrl->historial($crearRequest('/caja/historial', 'GET', ['q' => 'Gómez', 'tipo' => 'INGRESO']))->render());
file_put_contents($dirEvidencia . '/consultar-historial-cajas-paso2.html', $htmlHistorialFiltro);

$htmlHistorialBase = $sanitizar($cajaCtrl->historial($crearRequest('/caja/historial', 'GET'))->render());
file_put_contents($dirEvidencia . '/consultar-historial-cajas-paso3.html', $htmlHistorialBase);

// ──────────────────────────────────────────────────────────────────────────
// 17. consultar-clases-operativo
// ──────────────────────────────────────────────────────────────────────────
$htmlClasesIndex = $sanitizar($claseCtrl->index($crearRequest('/clases', 'GET'))->render());
file_put_contents($dirEvidencia . '/consultar-clases-operativo-paso1.html', $htmlClasesIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-clases-1366.html', $htmlClasesIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-clases-marco-375.html', $armarMarco375('signo-pregunta-clases-1366.html'));

$htmlClasesP2 = str_replace(
    'id="clase-hoy-' . $claseHoy->id . '"',
    'id="clase-hoy-' . $claseHoy->id . '" style="outline:3px solid var(--color-warning); outline-offset:2px;"',
    $htmlClasesIndex
);
file_put_contents($dirEvidencia . '/consultar-clases-operativo-paso2.html', $sanitizar($htmlClasesP2));

$htmlClasesP3 = $sanitizar($claseCtrl->index($crearRequest('/clases', 'GET', ['deporte_id' => $deportePatin->id]))->render());
file_put_contents($dirEvidencia . '/consultar-clases-operativo-paso3.html', $htmlClasesP3);

// ──────────────────────────────────────────────────────────────────────────
// 18. tomar-lista-mostrador
// ──────────────────────────────────────────────────────────────────────────
Asistencia::where('clase_id', $claseHoy->id)->delete();
$htmlClaseSinAsistencia = $sanitizar($claseCtrl->show($claseHoy->id)->render());
file_put_contents($dirEvidencia . '/tomar-lista-mostrador-paso1.html', $htmlClaseSinAsistencia);

Asistencia::create(['clase_id' => $claseHoy->id, 'alumno_id' => $martina->id, 'presente' => true, 'fecha_hora_marca' => Carbon::now()]);
$htmlClaseConAsistencia = $sanitizar($claseCtrl->show($claseHoy->id)->render());
file_put_contents($dirEvidencia . '/tomar-lista-mostrador-paso2.html', $htmlClaseConAsistencia);

// ──────────────────────────────────────────────────────────────────────────
// 19. resolver-revision-cobranza
// ──────────────────────────────────────────────────────────────────────────
$htmlRevisionIndex = $sanitizar($revCtrl->index($crearRequest('/revision-cobranza', 'GET'))->render());
file_put_contents($dirEvidencia . '/resolver-revision-cobranza-paso1.html', $htmlRevisionIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-revision-1366.html', $htmlRevisionIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-revision-marco-375.html', $armarMarco375('signo-pregunta-revision-1366.html'));

$htmlRevP2 = str_replace(
    'value="CONTINUA"',
    'value="CONTINUA" checked="checked"',
    $htmlRevisionIndex
);
$htmlRevP2 = preg_replace(
    '/(<form[^>]*action="[^"]*\/revision-cobranza\/[^"]*"[^>]*>)/',
    '<div style="outline:3px solid var(--color-brand); outline-offset:4px; border-radius:8px; padding:6px;">$1',
    $htmlRevP2
);
file_put_contents($dirEvidencia . '/resolver-revision-cobranza-paso2.html', $sanitizar($htmlRevP2));

$htmlRevP3 = str_replace('placeholder="Registrá el contacto y el motivo"', 'placeholder="Registrá el contacto y el motivo">Se habló con la madre, confirma que retoma el lunes tras viaje familiar</textarea>', $htmlRevP2);
$htmlRevP3 = preg_replace(
    '/(<button[^>]*type="submit"[^>]*>Confirmar<\/button>)/',
    '<span style="outline:3px solid var(--color-warning); outline-offset:2px; display:inline-block; border-radius:4px;">$1</span>',
    $htmlRevP3
);
file_put_contents($dirEvidencia . '/resolver-revision-cobranza-paso3.html', $sanitizar($htmlRevP3));

// ──────────────────────────────────────────────────────────────────────────
// 20. consultar-grupos-planes
// ──────────────────────────────────────────────────────────────────────────
$htmlGruposIndex = $sanitizar($grupoCtrl->index($crearRequest('/grupos', 'GET'))->render());
file_put_contents($dirEvidencia . '/consultar-grupos-planes-paso1.html', $htmlGruposIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-grupos-1366.html', $htmlGruposIndex);
file_put_contents($dirEvidencia . '/signo-pregunta-grupos-marco-375.html', $armarMarco375('signo-pregunta-grupos-1366.html'));

$htmlGruposP2 = $sanitizar($grupoCtrl->index($crearRequest('/grupos', 'GET', ['deporte_id' => $deportePatin->id]))->render());
file_put_contents($dirEvidencia . '/consultar-grupos-planes-paso2.html', $htmlGruposP2);

$htmlGruposP3 = preg_replace(
    '/(<div[^>]*class="[^"]*alumno-card[^"]*"[^>]*>)/',
    '$1<div style="outline:3px solid var(--color-brand); outline-offset:3px; border-radius:8px; margin:4px 0;">',
    $htmlGruposP2
);
file_put_contents($dirEvidencia . '/consultar-grupos-planes-paso3.html', $sanitizar($htmlGruposP3));

echo "Todos los HTML de pasos de Etapa A generados con éxito.\n";
