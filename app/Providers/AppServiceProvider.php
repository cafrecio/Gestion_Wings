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
                $badge = Clase::where('cancelada', false)
                    ->whereDate('fecha', '<', today())
                    ->whereDoesntHave('asistencias', fn($q) => $q->where('presente', true))
                    ->count();
            }
            $view->with('badgeClasesPendientes', $badge);
        });
    }
}
