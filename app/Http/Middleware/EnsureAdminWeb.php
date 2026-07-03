<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
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
