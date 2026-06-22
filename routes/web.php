<?php

use App\Http\Controllers\AlumnoWebController;
use App\Http\Controllers\CajaWebController;
use App\Http\Controllers\CashflowWebController;
use App\Http\Controllers\ConfiguracionWebController;
use App\Http\Controllers\MovimientoWebController;
use App\Http\Controllers\ReglaPrimerPagoWebController;
use App\Http\Controllers\UsuarioWebController;
use App\Http\Controllers\ClaseWebController;
use App\Http\Controllers\DeporteWebController;
use App\Http\Controllers\GrupoWebController;
use App\Http\Controllers\LiquidacionWebController;
use App\Http\Controllers\NivelWebController;
use App\Http\Controllers\ProfesorWebController;
use App\Http\Controllers\TipoCajaWebController;
use App\Http\Controllers\RubroWebController;
use App\Http\Controllers\SubrubroWebController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [WebController::class, 'loginForm'])->name('login');
Route::post('/login', [WebController::class, 'login']);
Route::post('/logout', [WebController::class, 'logout'])->name('logout');
Route::get('/logout', fn() => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    // ── Caja: rutas estáticas ANTES de las parametrizadas ─────────────────
    Route::get('/caja', [CajaWebController::class, 'index'])->name('web.caja.index');
    Route::get('/caja/movimiento', [CajaWebController::class, 'movimientoForm'])->name('web.caja.movimiento');
    Route::post('/caja/movimiento', [CajaWebController::class, 'movimientoStore'])->name('web.caja.movimiento.store');
    Route::get('/caja/cobrar', [CajaWebController::class, 'cobrarCuotaSelect'])->name('web.caja.cobrar-cuota');
    Route::get('/caja/cobrar/{alumnoId}', [CajaWebController::class, 'cobrar'])->name('web.caja.cobrar');
    Route::post('/caja/cobrar/{alumnoId}', [CajaWebController::class, 'pagar'])->name('web.caja.pagar');

    // Vistas de una caja específica (resumen, detalle, editar)
    Route::get('/caja/{id}/resumen', [CajaWebController::class, 'resumen'])->name('web.caja.resumen');
    Route::get('/caja/{id}/detalle', [CajaWebController::class, 'detalle'])->name('web.caja.detalle');
    Route::get('/caja/{id}/editar', [CajaWebController::class, 'editarForm'])->name('web.caja.editar');
    Route::post('/caja/{id}/editar', [CajaWebController::class, 'editarStore'])->name('web.caja.editar.store');
    Route::post('/caja/{id}/cerrar', [CajaWebController::class, 'cerrar'])->name('web.caja.cerrar');

    // Editar / eliminar / cancelar movimiento individual
    Route::get('/caja/{cajaId}/movimientos/{movId}/editar', [CajaWebController::class, 'editarMovimientoForm'])->name('web.caja.movimientos.editar');
    Route::get('/caja/{cajaId}/movimientos/{movId}/cancelar', [CajaWebController::class, 'cancelarMovimientoForm'])->name('web.caja.movimientos.cancelar');
    Route::post('/caja/{cajaId}/movimientos/{movId}/cancelar', [CajaWebController::class, 'cancelarMovimiento'])->name('web.caja.movimientos.cancelar.store');
    Route::put('/caja/{cajaId}/movimientos/{movId}', [CajaWebController::class, 'updateMovimiento'])->name('web.caja.movimientos.update');
    Route::delete('/caja/{cajaId}/movimientos/{movId}', [CajaWebController::class, 'destroyMovimiento'])->name('web.caja.movimientos.destroy');

    // /cajas → alias de /caja
    Route::get('/cajas', fn() => redirect()->route('web.caja.index'))->name('web.cajas.index');
    // /cajas/{id} → compat con links viejos
    Route::get('/cajas/{id}', fn($id) => redirect()->route('web.caja.detalle', $id))->name('web.cajas.show');

    // Compatibilidad backward — vieja ruta de cobro
    Route::get('/caja/{id}/cobrar', fn($id) => redirect()->route('web.caja.cobrar', $id));

    // ── Admin: validación/rechazo de cajas ────────────────────────────────
    Route::middleware('ensure.admin.web')->group(function () {
        Route::post('/cajas/{id}/validar', [CajaWebController::class, 'validar'])->name('web.cajas.validar');
        Route::post('/cajas/{id}/rechazar', [CajaWebController::class, 'rechazar'])->name('web.cajas.rechazar');
    });

    // ── Movimientos (admin y operativo) ──────────────────────────────────
    Route::get('/movimientos', [MovimientoWebController::class, 'index'])->name('web.movimientos.index');

    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/admin/dashboard', [WebController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/cashflow/movimiento', [CashflowWebController::class, 'create'])->name('web.cashflow.movimiento');
        Route::post('/cashflow/movimiento', [CashflowWebController::class, 'store'])->name('web.cashflow.movimiento.store');
    });

    // Alumnos CRUD — accesible para todos los roles autenticados (ADMIN y OPERATIVO)
    Route::get('/alumnos', [AlumnoWebController::class, 'index'])->name('web.alumnos.index');
    Route::get('/alumnos/autocomplete', [AlumnoWebController::class, 'autocomplete'])->name('web.alumnos.autocomplete');
    Route::get('/alumnos/create', [AlumnoWebController::class, 'create'])->name('web.alumnos.create');
    Route::post('/alumnos', [AlumnoWebController::class, 'store'])->name('web.alumnos.store');
    Route::get('/alumnos/{id}', [AlumnoWebController::class, 'show'])->name('web.alumnos.show');
    Route::get('/alumnos/{id}/edit', [AlumnoWebController::class, 'edit'])->name('web.alumnos.edit');
    Route::put('/alumnos/{id}', [AlumnoWebController::class, 'update'])->name('web.alumnos.update');
    Route::patch('/alumnos/{id}/toggle-activo', [AlumnoWebController::class, 'toggleActivo'])->name('web.alumnos.toggle-activo');

    // Deportes CRUD (admin only)
    Route::get('/deportes', [DeporteWebController::class, 'index'])->name('web.deportes.index');
    Route::get('/deportes/create', [DeporteWebController::class, 'create'])->name('web.deportes.create');
    Route::post('/deportes', [DeporteWebController::class, 'store'])->name('web.deportes.store');
    Route::get('/deportes/{id}/edit', [DeporteWebController::class, 'edit'])->name('web.deportes.edit');
    Route::put('/deportes/{id}', [DeporteWebController::class, 'update'])->name('web.deportes.update');
    Route::patch('/deportes/{id}/toggle-activo', [DeporteWebController::class, 'toggleActivo'])->name('web.deportes.toggle-activo');

    // Rubros CRUD
    Route::get('/rubros', [RubroWebController::class, 'index'])->name('web.rubros.index');
    Route::get('/rubros/create', [RubroWebController::class, 'create'])->name('web.rubros.create');
    Route::post('/rubros', [RubroWebController::class, 'store'])->name('web.rubros.store');
    Route::get('/rubros/{id}/edit', [RubroWebController::class, 'edit'])->name('web.rubros.edit');
    Route::put('/rubros/{id}', [RubroWebController::class, 'update'])->name('web.rubros.update');
    Route::delete('/rubros/{id}', [RubroWebController::class, 'destroy'])->name('web.rubros.destroy');

    // Subrubros CRUD (nested under rubros)
    Route::get('/rubros/{rubroId}/subrubros/create', [SubrubroWebController::class, 'create'])->name('web.subrubros.create');
    Route::post('/rubros/{rubroId}/subrubros', [SubrubroWebController::class, 'store'])->name('web.subrubros.store');
    Route::get('/rubros/{rubroId}/subrubros/{id}/edit', [SubrubroWebController::class, 'edit'])->name('web.subrubros.edit');
    Route::put('/rubros/{rubroId}/subrubros/{id}', [SubrubroWebController::class, 'update'])->name('web.subrubros.update');
    Route::delete('/rubros/{rubroId}/subrubros/{id}', [SubrubroWebController::class, 'destroy'])->name('web.subrubros.destroy');

    // Grupos — lectura para todos los autenticados
    Route::get('/grupos', [GrupoWebController::class, 'index'])->name('web.grupos.index');

    // Grupos — escritura solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/grupos/create', [GrupoWebController::class, 'create'])->name('web.grupos.create');
        Route::post('/grupos', [GrupoWebController::class, 'store'])->name('web.grupos.store');
        Route::get('/grupos/{id}/edit', [GrupoWebController::class, 'edit'])->name('web.grupos.edit');
        Route::put('/grupos/{id}', [GrupoWebController::class, 'update'])->name('web.grupos.update');
        Route::patch('/grupos/{id}/toggle-activo', [GrupoWebController::class, 'toggleActivo'])->name('web.grupos.toggle-activo');
        Route::post('/grupos/{id}/planes', [GrupoWebController::class, 'storePlan'])->name('web.grupos.plans.store');
        Route::delete('/grupo-planes/{planId}', [GrupoWebController::class, 'destroyPlan'])->name('web.grupos.plans.destroy');
    });

    // Grupos check-disponible — debe ir ANTES de /grupos/{id}
    Route::get('/grupos/check-disponible', [GrupoWebController::class, 'checkDisponible'])->name('web.grupos.check-disponible');

    // Grupos show — todos los autenticados (después de /create para evitar conflicto de rutas)
    Route::get('/grupos/{id}', [GrupoWebController::class, 'show'])->name('web.grupos.show');

    // Clases — escritura solo admin (PRIMERO para que /clases/create no colisione con /clases/{id})
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/clases/create', [ClaseWebController::class, 'create'])->name('web.clases.create');
        Route::post('/clases', [ClaseWebController::class, 'store'])->name('web.clases.store');
        Route::get('/clases/{id}/edit', [ClaseWebController::class, 'edit'])->name('web.clases.edit');
        Route::put('/clases/{id}', [ClaseWebController::class, 'update'])->name('web.clases.update');
        Route::patch('/clases/{id}/validar', [ClaseWebController::class, 'toggleValidada'])->name('web.clases.toggle-validada');
    });
    // Clases — accesibles para todos los roles autenticados
    Route::get('/clases', [ClaseWebController::class, 'index'])->name('web.clases.index');
    Route::get('/clases/{id}', [ClaseWebController::class, 'show'])->name('web.clases.show');
    Route::post('/clases/{id}/asistencias', [ClaseWebController::class, 'storeAsistencias'])->name('web.clases.asistencias');
    Route::patch('/clases/{id}/cancelar', [ClaseWebController::class, 'toggleCancelada'])->name('web.clases.toggle-cancelada');
    Route::patch('/clases/{id}/profesores', [ClaseWebController::class, 'actualizarProfesores'])->name('web.clases.profesores');

    // Liquidaciones — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/liquidaciones', [LiquidacionWebController::class, 'index'])->name('web.liquidaciones.index');
        Route::get('/liquidaciones/crear', [LiquidacionWebController::class, 'create'])->name('web.liquidaciones.create');
        Route::post('/liquidaciones', [LiquidacionWebController::class, 'store'])->name('web.liquidaciones.store');
        Route::get('/liquidaciones/{id}', [LiquidacionWebController::class, 'show'])->name('web.liquidaciones.show');
        Route::post('/liquidaciones/{id}/cerrar', [LiquidacionWebController::class, 'cerrar'])->name('web.liquidaciones.cerrar');
        Route::post('/liquidaciones/{id}/recalcular', [LiquidacionWebController::class, 'recalcular'])->name('web.liquidaciones.recalcular');
        Route::delete('/liquidaciones/{id}', [LiquidacionWebController::class, 'eliminar'])->name('web.liquidaciones.eliminar');
        Route::post('/liquidaciones/{id}/pagar', [LiquidacionWebController::class, 'pagar'])->name('web.liquidaciones.pagar');
    });

    // Tipos de Caja — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/tipos-caja', [TipoCajaWebController::class, 'index'])->name('web.tipos-caja.index');
        Route::get('/tipos-caja/create', [TipoCajaWebController::class, 'create'])->name('web.tipos-caja.create');
        Route::post('/tipos-caja', [TipoCajaWebController::class, 'store'])->name('web.tipos-caja.store');
        Route::get('/tipos-caja/check-disponible', [TipoCajaWebController::class, 'checkDisponible'])->name('web.tipos-caja.check-disponible');
        Route::get('/tipos-caja/{id}/edit', [TipoCajaWebController::class, 'edit'])->name('web.tipos-caja.edit');
        Route::put('/tipos-caja/{id}', [TipoCajaWebController::class, 'update'])->name('web.tipos-caja.update');
        Route::delete('/tipos-caja/{id}', [TipoCajaWebController::class, 'destroy'])->name('web.tipos-caja.destroy');
    });

    // Niveles — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/niveles', [NivelWebController::class, 'index'])->name('web.niveles.index');
        Route::get('/niveles/create', [NivelWebController::class, 'create'])->name('web.niveles.create');
        Route::post('/niveles', [NivelWebController::class, 'store'])->name('web.niveles.store');
        Route::get('/niveles/check-disponible', [NivelWebController::class, 'checkDisponible'])->name('web.niveles.check-disponible');
        Route::get('/niveles/{id}/edit', [NivelWebController::class, 'edit'])->name('web.niveles.edit');
        Route::put('/niveles/{id}', [NivelWebController::class, 'update'])->name('web.niveles.update');
        Route::delete('/niveles/{id}', [NivelWebController::class, 'destroy'])->name('web.niveles.destroy');
    });

    // Usuarios — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/usuarios', [UsuarioWebController::class, 'index'])->name('web.usuarios.index');
        Route::get('/usuarios/create', [UsuarioWebController::class, 'create'])->name('web.usuarios.create');
        Route::post('/usuarios', [UsuarioWebController::class, 'store'])->name('web.usuarios.store');
        Route::get('/usuarios/check-email', [UsuarioWebController::class, 'checkEmail'])->name('web.usuarios.check-email');
        Route::get('/usuarios/{id}/edit', [UsuarioWebController::class, 'edit'])->name('web.usuarios.edit');
        Route::put('/usuarios/{id}', [UsuarioWebController::class, 'update'])->name('web.usuarios.update');
        Route::patch('/usuarios/{id}/toggle-activo', [UsuarioWebController::class, 'toggleActivo'])->name('web.usuarios.toggle-activo');
    });

    // Configuraciones — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/configuraciones', [ConfiguracionWebController::class, 'index'])->name('web.configuraciones.index');
        // Reglas primer pago — ANTES de /configuraciones/{clave}
        Route::post('/configuraciones/primer-pago', [ReglaPrimerPagoWebController::class, 'store'])->name('web.config.primer-pago.store');
        Route::put('/configuraciones/primer-pago/{id}', [ReglaPrimerPagoWebController::class, 'update'])->name('web.config.primer-pago.update');
        Route::delete('/configuraciones/primer-pago/{id}', [ReglaPrimerPagoWebController::class, 'destroy'])->name('web.config.primer-pago.destroy');
        Route::patch('/configuraciones/{clave}', [ConfiguracionWebController::class, 'update'])->name('web.configuraciones.update');
    });

    // Profesores — solo admin
    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/profesores', [ProfesorWebController::class, 'index'])->name('web.profesores.index');
        Route::get('/profesores/create', [ProfesorWebController::class, 'create'])->name('web.profesores.create');
        Route::post('/profesores', [ProfesorWebController::class, 'store'])->name('web.profesores.store');
        Route::get('/profesores/{id}', [ProfesorWebController::class, 'show'])->name('web.profesores.show');
        Route::get('/profesores/{id}/edit', [ProfesorWebController::class, 'edit'])->name('web.profesores.edit');
        Route::put('/profesores/{id}', [ProfesorWebController::class, 'update'])->name('web.profesores.update');
        Route::patch('/profesores/{id}/toggle-activo', [ProfesorWebController::class, 'toggleActivo'])->name('web.profesores.toggle-activo');
    });
});
