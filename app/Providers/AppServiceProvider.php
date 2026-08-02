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
                // Misma consulta que el filtro estado=finalizada de
                // ClaseWebController::index(), porque el badge es un acceso
                // directo a ese listado: si contara distinto, el número
                // anunciado no coincidiría con lo que se ve al hacer click.
                $query = Clase::where('cancelada', false)
                    ->whereDate('fecha', '<', today())
                    ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true));

                // El listado recorta a 35 días hacia atrás para los no-admin.
                if (!Auth::user()->isAdmin()) {
                    $query->whereDate('fecha', '>=', today()->subDays(35)->format('Y-m-d'));
                }

                $badge = $query->count();
            }
            $view->with('badgeClasesPendientes', $badge);
        });
    }
}
