<?php

namespace App\Providers;

use App\Models\Clase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $badge = 0;
            if (Auth::check()) {
                // Clases pasadas que NO se van a poder liquidar hasta que
                // alguien las resuelva: sin asistencia completada no se le
                // paga al profesor. Por eso se cuentan TODAS, sin ventana de
                // tiempo — una clase de enero sin cargar sigue trabando plata.
                //
                // Se excluyen las validadas para liquidación a mano: ya están
                // resueltas y se pagan igual aunque no tengan asistencia
                // (ver LIQUIDACIONES_CONTRATO_V2.md, 2.3).
                //
                // Misma consulta que el filtro estado=finalizada de
                // ClaseWebController::index(), porque el badge es un acceso
                // directo a ese listado y el número tiene que coincidir.
                $badge = Clase::where('cancelada', false)
                    ->whereDate('fecha', '<', today())
                    ->where('validada_para_liquidacion', false)
                    ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))
                    ->count();
            }
            $view->with('badgeClasesPendientes', $badge);
        });
    }
}
