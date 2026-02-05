<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\CajaOperativaController;
use App\Http\Controllers\CashflowMovimientoController;
use App\Http\Controllers\CashflowSaldoController;
use App\Http\Controllers\CierreDiaController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\MovimientoOperativoController;
use App\Http\Controllers\OperativoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoCuotaController;
use App\Http\Controllers\ReciboController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas de Alumnos
Route::apiResource('alumnos', AlumnoController::class);

// Rutas de Pagos
Route::prefix('pagos')->group(function () {
    Route::post('/', [PagoController::class, 'store']);
});

// Rutas específicas de Alumno relacionadas con Pagos
Route::prefix('alumnos/{alumnoId}')->group(function () {
    Route::get('/pagos', [PagoController::class, 'index']);
    Route::get('/proximo-pago', [PagoController::class, 'proximoPago']);
    Route::get('/puede-pagar', [PagoController::class, 'verificarPuedePagar']);
    Route::post('/cambiar-plan', [PagoController::class, 'cambiarPlan']);
});

// Rutas de Reglas de Primer Pago
Route::get('/reglas-primer-pago/dia/{dia}', [PagoController::class, 'reglasDisponibles']);

// Rutas de Clases
Route::prefix('clases')->group(function () {
    Route::post('/', [ClaseController::class, 'store']);
    Route::post('/asignar-profesor', [ClaseController::class, 'asignarProfesor']);
    Route::get('/{claseId}/profesor/{profesorId}/disponibilidad', [ClaseController::class, 'verificarDisponibilidadProfesor']);
});

// Rutas de Asistencias
Route::prefix('asistencias')->group(function () {
    Route::post('/', [AsistenciaController::class, 'store']);
    Route::get('/clase/{claseId}/alumno/{alumnoId}/disponibilidad', [AsistenciaController::class, 'verificarDisponibilidadAlumno']);
});

// Rutas de Liquidaciones
Route::prefix('liquidaciones')->group(function () {
    Route::post('/', [LiquidacionController::class, 'store']);
    Route::get('/preview', [LiquidacionController::class, 'preview']);
    Route::get('/resumen/{mes}/{anio}', [LiquidacionController::class, 'resumenPeriodo']);
    Route::get('/{id}', [LiquidacionController::class, 'show']);
    Route::delete('/{id}', [LiquidacionController::class, 'destroy']);
    Route::post('/{id}/cerrar', [LiquidacionController::class, 'cerrar']);
    Route::post('/{id}/recalcular', [LiquidacionController::class, 'recalcular']);
});

// Rutas de Liquidaciones por Profesor
Route::get('/profesores/{profesorId}/liquidaciones', [LiquidacionController::class, 'indexByProfesor']);

// ========================================
// RUTAS DE CAJA OPERATIVA Y CASHFLOW
// ========================================

// Rutas de Movimientos Operativos (con bloqueo por caja vieja)
Route::middleware(['bloqueo.caja.vieja'])->group(function () {
    Route::post('/movimientos-operativos', [MovimientoOperativoController::class, 'store']);
});

// Rutas de Cajas Operativas
Route::prefix('cajas')->group(function () {
    // Consultas (sin bloqueo - permitidas siempre)
    Route::get('/', [CajaOperativaController::class, 'index']);
    Route::get('/abierta', [CajaOperativaController::class, 'cajaAbierta']);
    Route::get('/mias', [CajaOperativaController::class, 'misCajas']);
    Route::get('/pendientes', [CajaOperativaController::class, 'pendientes']);
    Route::get('/{id}', [CajaOperativaController::class, 'show']);
    Route::get('/{id}/resumen', [CajaOperativaController::class, 'resumen']);
    Route::get('/{cajaId}/movimientos', [MovimientoOperativoController::class, 'indexByCaja']);

    // Acciones Operativo (con bloqueo - pero permite cerrar caja vieja propia)
    Route::middleware(['bloqueo.caja.vieja'])->group(function () {
        Route::post('/{id}/cerrar', [CajaOperativaController::class, 'cerrar']);
    });

    // Acciones Admin (protegidas con ensure.admin)
    Route::middleware(['ensure.admin'])->group(function () {
        Route::post('/{id}/cerrar-admin', [CajaOperativaController::class, 'cerrarComoAdmin']);
        Route::post('/{id}/validar', [CajaOperativaController::class, 'validar']);
        Route::post('/{id}/rechazar', [CajaOperativaController::class, 'rechazar']);
    });
});

// Rutas de Cashflow (solo Admin)
Route::prefix('cashflow-movimientos')->middleware(['ensure.admin'])->group(function () {
    Route::get('/', [CashflowMovimientoController::class, 'index']);
    Route::post('/', [CashflowMovimientoController::class, 'store']);
    Route::get('/{id}', [CashflowMovimientoController::class, 'show']);
});

// ========================================
// RUTAS DE CIERRE DEL DÍA (REPORTES)
// ========================================

// Cierre del día - Operativo (usuario autenticado)
Route::get('/cierres-dia', [CierreDiaController::class, 'operativo']);

// ========================================
// RUTAS DE OPERATIVO
// ========================================

// Estado operativo del día (usuario autenticado)
Route::get('/operativo/estado-hoy', [OperativoController::class, 'estadoHoy']);

// ========================================
// RUTAS DE PAGO DE CUOTAS - OPERATIVO
// ========================================

// Pago de cuota - Operativo (con bloqueo por caja vieja)
Route::middleware(['bloqueo.caja.vieja'])->group(function () {
    Route::post('/cuotas/pagos', [PagoCuotaController::class, 'storeOperativo']);
});

// Consultas de deudas (público/operativo)
Route::get('/alumnos/{alumnoId}/deudas', [PagoCuotaController::class, 'indexByAlumno']);
Route::get('/deudas/{id}', [PagoCuotaController::class, 'show']);

// ========================================
// RUTAS DE ADMIN (protegidas con ensure.admin)
// ========================================

Route::prefix('admin')->middleware(['ensure.admin'])->group(function () {
    // Cierre del día - Admin (global o por operativo)
    Route::get('/cierres-dia', [CierreDiaController::class, 'admin']);

    // Saldos cashflow por tipo de caja (actuales)
    Route::get('/cashflow/saldos', [CashflowSaldoController::class, 'index']);

    // Saldo cashflow acumulado hasta una fecha
    Route::get('/cashflow/saldo', [CashflowSaldoController::class, 'saldoAFecha']);

    // Pago de cuota - Admin
    Route::post('/cuotas/pagos', [PagoCuotaController::class, 'storeAdmin']);

    // Gestión de deudas - Admin
    Route::prefix('cuotas/deudas')->group(function () {
        Route::post('/', [PagoCuotaController::class, 'storeDeuda']);
        Route::post('/{id}/condonar', [PagoCuotaController::class, 'condonar']);
        Route::post('/{id}/ajustar', [PagoCuotaController::class, 'ajustar']);
    });

    // Pago de liquidaciones - Admin
    Route::post('/liquidaciones/{id}/pagar', [LiquidacionController::class, 'pagar']);
});

// ========================================
// RUTAS DE RECIBOS PDF
// ========================================

Route::prefix('recibos')->group(function () {
    // Recibo de pago de cuota
    // TODO: Cuando haya auth real, operativo solo puede acceder a pagos operativos
    Route::get('/cuota/{pagoId}', [ReciboController::class, 'cuota']);
    Route::get('/cuota/{pagoId}/info', [ReciboController::class, 'infoCuota']);

    // Recibo de liquidación (solo admin debería acceder)
    // TODO: Proteger con middleware ensure.admin cuando haya auth real
    Route::get('/liquidacion/{liquidacionId}', [ReciboController::class, 'liquidacion']);
    Route::get('/liquidacion/{liquidacionId}/info', [ReciboController::class, 'infoLiquidacion']);
});
