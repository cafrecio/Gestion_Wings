<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectProfesorWeb
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->rol === User::ROL_PROFESOR) {
            abort(403);
        }

        return $next($request);
    }
}
