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
        if (($alta || ($request->user()->isAdmin() && $request->routeIs('admin.dashboard', 'web.reportes.*'))) && PrimeraCarga::pendiente()) {
            if (!$request->user()->isAdmin()) abort(403, 'ADMIN debe completar la primera carga del club.');
            // Sin el aviso, tocar Inicio, Alumnos o Reportes en el menú parecía no hacer
            // nada: el sistema volvía a esta misma pantalla sin decir por qué (Carlos, 10/10).
            return redirect()->route('web.primera-carga.index')
                ->with('error', 'Primero terminá la primera carga de alumnos. Inicio, Alumnos y Reportes se habilitan cuando esté hecha.');
        }
        return $next($request);
    }
}
