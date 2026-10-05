<?php

namespace App\Http\Middleware;

use App\Models\PrimeraCarga;
use Closure;
use Illuminate\Http\Request;

class PrepararPrimeraCarga
{
    public function handle(Request $request, Closure $next)
    {
        $alta = $request->routeIs('web.alumnos.*');
        if (($alta || ($request->user()->isAdmin() && $request->routeIs('admin.dashboard'))) && PrimeraCarga::pendiente()) {
            if (!$request->user()->isAdmin()) abort(403, 'ADMIN debe completar la primera carga del club.');
            return redirect()->route('web.primera-carga.index');
        }
        return $next($request);
    }
}
