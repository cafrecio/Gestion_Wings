<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
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

        if ($request->user()->rol !== 'ADMIN') {
            if ($request->user()->rol === 'PROFESOR') {
                return redirect()->route('web.clases.index');
            }
            return redirect()->route('web.caja.index');
        }

        return $next($request);
    }
}
