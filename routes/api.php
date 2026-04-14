<?php

use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaOperativaController;
use App\Http\Controllers\CashflowMovimientoController;
use App\Http\Controllers\CashflowSaldoController;
use App\Http\Controllers\CierreDiaController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\CobranzaController;
use App\Http\Controllers\LiquidacionController;
use App\Http\Controllers\MovimientoOperativoController;
use App\Http\Controllers\OperativoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoCuotaController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\Admin\AlumnoController as AdminAlumnoController;
use App\Http\Controllers\Admin\ClaseController as AdminClaseController;
use App\Http\Controllers\Admin\DeporteController;
use App\Http\Controllers\Admin\GrupoController;
use App\Http\Controllers\Admin\ProfesorController;
use App\Http\Controllers\Admin\RubroController;
use App\Http\Controllers\Admin\SubrubroController;
use App\Http\Controllers\Admin\TipoCajaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ========================================
// RUTAS DE AUTENTICACIÓN (públicas)
// ========================================

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ========================================
// RUTAS PÚBLICAS (consulta sin auth)
// ========================================

// Consultas de deudas — movidas a rutas protegidas (ver abajo)

// Rutas de Reglas de Primer Pago
Route::get('/reglas-primer-pago/dia/{dia}', [PagoController::class, 'reglasDisponibles']);

// ========================================
// RUTAS PROTEGIDAS (requieren auth:sanctum)
// ========================================

Route::middleware('auth:sanctum')->group(function () {

    // Usuario actual (legacy)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rutas de Alumnos
    Route::apiResource('alumnos', AlumnoController::class);

    // Consultas de deudas
    Route::get('/alumnos/{alumnoId}/deudas', [PagoCuotaController::class, 'indexByAlumno']);
    Route::get('/deudas/{id}', [PagoCuotaController::class, 'show']);

    // Rutas de Pagos
    Route::prefix('pagos')->group(function () {
        Route::post('/', [PagoController::class, 'store']);
    });

    // Estado de cobranza individual
    Route::get('/alumnos/{id}/estado-cobranza', [CobranzaController::class, 'estadoAlumno']);

    // Filtrar alumnos por estado de cobranza
    Route::get('/cobranza/alumnos', [CobranzaController::class, 'alumnosPorEstado']);

    // Rutas específicas de Alumno relacionadas con Pagos
    Route::prefix('alumnos/{alumnoId}')->group(function () {
        Route::get('/pagos', [PagoController::class, 'index']);
        Route::get('/proximo-pago', [PagoController::class, 'proximoPago']);
        Route::get('/puede-pagar', [PagoController::class, 'verificarPuedePagar']);
        Route::post('/cambiar-plan', [PagoController::class, 'cambiarPlan']);
    });

    // Rutas de Clases
    Route::prefix('clases')->group(function () {
        Route::post('/', [ClaseController::class, 'store']);
        Route::post('/asignar-profesor', [ClaseController::class, 'asignarProfesor']);
        Route::get('/{claseId}/profesor/{profesorId}/disponibilidad', [ClaseController::class, 'verificarDisponibilidadProfesor']);
    });

    // Rutas de Asistencias (OPERATIVO + ADMIN)
    Route::prefix('asistencias')->group(function () {
        Route::post('/', [AsistenciaController::class, 'store']);
        Route::get('/clase/{claseId}/alumno/{alumnoId}/disponibilidad', [AsistenciaController::class, 'verificarDisponibilidadAlumno']);

        // Listar asistencias de una clase
        Route::get('/clase/{claseId}', [AsistenciaController::class, 'indexByClase']);

        // Registrar asistencias bulk (con bloqueo por caja vieja)
        Route::middleware(['bloqueo.caja.vieja'])->group(function () {
            Route::post('/clase/{claseId}', [AsistenciaController::class, 'storeBulk']);
        });
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

        // Dashboard y revisión de cobranza
        Route::prefix('cobranza')->group(function () {
            Route::get('/dashboard', [CobranzaController::class, 'dashboard']);
            Route::get('/revision', [CobranzaController::class, 'indexRevision']);
            Route::post('/revision/{id}/resolver', [CobranzaController::class, 'resolverRevision']);
        });

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

        // ========================================
        // ABM - CRUD ADMIN
        // ========================================

        // ABM-1: Deportes
        Route::prefix('deportes')->group(function () {
            Route::get('/', [DeporteController::class, 'index']);
            Route::get('/{id}', [DeporteController::class, 'show']);
            Route::post('/', [DeporteController::class, 'store']);
            Route::put('/{id}', [DeporteController::class, 'update']);
            Route::delete('/{id}', [DeporteController::class, 'destroy']);
        });

        // ABM-2: Grupos
        Route::prefix('grupos')->group(function () {
            Route::get('/', [GrupoController::class, 'index']);
            Route::get('/{id}', [GrupoController::class, 'show']);
            Route::post('/', [GrupoController::class, 'store']);
            Route::put('/{id}', [GrupoController::class, 'update']);
            Route::delete('/{id}', [GrupoController::class, 'destroy']);
        });

        // ABM-3: Profesores
        Route::prefix('profesores')->group(function () {
            Route::get('/', [ProfesorController::class, 'index']);
            Route::get('/{id}', [ProfesorController::class, 'show']);
            Route::post('/', [ProfesorController::class, 'store']);
            Route::put('/{id}', [ProfesorController::class, 'update']);
            Route::delete('/{id}', [ProfesorController::class, 'destroy']);
        });

        // ABM-4: Alumnos
        Route::prefix('alumnos')->group(function () {
            Route::get('/', [AdminAlumnoController::class, 'index']);
            Route::get('/{id}', [AdminAlumnoController::class, 'show']);
            Route::post('/', [AdminAlumnoController::class, 'store']);
            Route::put('/{id}', [AdminAlumnoController::class, 'update']);
            Route::delete('/{id}', [AdminAlumnoController::class, 'destroy']);
        });

        // ABM-5: Clases
        Route::prefix('clases')->group(function () {
            Route::get('/', [AdminClaseController::class, 'index']);
            Route::get('/{id}', [AdminClaseController::class, 'show']);
            Route::post('/', [AdminClaseController::class, 'store']);
            Route::put('/{id}', [AdminClaseController::class, 'update']);
            Route::delete('/{id}', [AdminClaseController::class, 'destroy']);
        });

        // ABM-7: Catálogos contables
        // Rubros
        Route::prefix('rubros')->group(function () {
            Route::get('/', [RubroController::class, 'index']);
            Route::get('/{id}', [RubroController::class, 'show']);
            Route::post('/', [RubroController::class, 'store']);
            Route::put('/{id}', [RubroController::class, 'update']);
            Route::delete('/{id}', [RubroController::class, 'destroy']);
        });

        // Subrubros
        Route::prefix('subrubros')->group(function () {
            Route::get('/', [SubrubroController::class, 'index']);
            Route::get('/{id}', [SubrubroController::class, 'show']);
            Route::post('/', [SubrubroController::class, 'store']);
            Route::put('/{id}', [SubrubroController::class, 'update']);
            Route::delete('/{id}', [SubrubroController::class, 'destroy']);
        });

        // Tipos de Caja
        Route::prefix('tipos-caja')->group(function () {
            Route::get('/', [TipoCajaController::class, 'index']);
            Route::get('/{id}', [TipoCajaController::class, 'show']);
            Route::post('/', [TipoCajaController::class, 'store']);
            Route::put('/{id}', [TipoCajaController::class, 'update']);
            Route::delete('/{id}', [TipoCajaController::class, 'destroy']);
        });
    });

    // ========================================
    // RUTAS DE RECIBOS PDF
    // ========================================

    Route::prefix('recibos')->group(function () {
        // Recibo de pago de cuota
        Route::get('/cuota/{pagoId}', [ReciboController::class, 'cuota']);
        Route::get('/cuota/{pagoId}/info', [ReciboController::class, 'infoCuota']);

        // Recibo de liquidación (solo admin)
        Route::middleware(['ensure.admin'])->group(function () {
            Route::get('/liquidacion/{liquidacionId}', [ReciboController::class, 'liquidacion']);
            Route::get('/liquidacion/{liquidacionId}/info', [ReciboController::class, 'infoLiquidacion']);
        });
    });
});
