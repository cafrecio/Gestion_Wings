<?php

use App\Http\Controllers\AlumnoWebController;
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

    Route::middleware('ensure.admin.web')->group(function () {
        Route::get('/admin/dashboard', [WebController::class, 'adminDashboard'])->name('admin.dashboard');
    });

    // Alumnos CRUD
    Route::get('/alumnos', [AlumnoWebController::class, 'index'])->name('web.alumnos.index');
    Route::get('/alumnos/create', [AlumnoWebController::class, 'create'])->name('web.alumnos.create')->middleware('ensure.admin.web');
    Route::post('/alumnos', [AlumnoWebController::class, 'store'])->name('web.alumnos.store')->middleware('ensure.admin.web');
    Route::get('/alumnos/{id}', [AlumnoWebController::class, 'show'])->name('web.alumnos.show');
    Route::get('/alumnos/{id}/edit', [AlumnoWebController::class, 'edit'])->name('web.alumnos.edit')->middleware('ensure.admin.web');
    Route::put('/alumnos/{id}', [AlumnoWebController::class, 'update'])->name('web.alumnos.update')->middleware('ensure.admin.web');
});
