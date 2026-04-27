<?php

use App\Http\Controllers\AlumnoWebController;
use App\Http\Controllers\ClaseWebController;
use App\Http\Controllers\DeporteWebController;
use App\Http\Controllers\GrupoWebController;
use App\Http\Controllers\NivelWebController;
use App\Http\Controllers\ProfesorWebController;
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

Route::middleware('auth')->group(function () {
    Route::get('/caja', [WebController::class, 'caja'])->name('operativo.caja');
    Route::get('/caja/{id}/cobrar', [WebController::class, 'cajaAlumnoCobro'])->name('operativo.caja.cobrar');
    Route::post('/caja/{id}/cobrar', [WebController::class, 'cajaRegistrarPago'])->name('operativo.caja.pagar');

    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/admin/dashboard', [WebController::class, 'adminDashboard'])->name('admin.dashboard');
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
