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
            return redirect()->route('operativo.caja');
        }

        return $next($request);
    }
}
