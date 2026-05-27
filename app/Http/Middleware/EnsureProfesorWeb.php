<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfesorWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->rol !== User::ROL_PROFESOR) {
            return match ($request->user()->rol) {
                User::ROL_ADMIN    => redirect()->route('admin.dashboard'),
                default            => redirect()->route('operativo.caja'),
            };
        }

        return $next($request);
    }
}
