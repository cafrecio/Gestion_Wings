<?php

namespace App\Http\Middleware;

use App\Services\LegalService;
use Closure;
use Illuminate\Http\Request;

class ExigirAceptacionLegal
{
    public function __construct(private LegalService $legal) {}

    public function handle(Request $request, Closure $next)
    {
        if (!config('legal.exigir') || $request->routeIs('web.legal.*')) {
            return $next($request);
        }

        if (!$this->legal->aceptacionVigente($request->user())) {
            return redirect()->route('web.legal.aceptar');
        }

        return $next($request);
    }
}
