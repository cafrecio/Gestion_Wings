<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfesorWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->isActivo()) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta fue desactivada.']);
        }

        if ($request->user()->rol !== User::ROL_PROFESOR) {
            return match ($request->user()->rol) {
                User::ROL_ADMIN    => redirect()->route('admin.dashboard'),
                default            => redirect()->route('web.caja.index'),
            };
        }

        return $next($request);
    }
}
