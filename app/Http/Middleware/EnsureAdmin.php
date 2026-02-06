<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Middleware provisorio para proteger rutas admin.
     * Verifica que el usuario autenticado tenga role/rol = 'ADMIN'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // No autenticado
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                ],
            ], 401);
        }

        // Verificar rol admin
        $isAdmin = $user->rol === 'ADMIN';

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN_ADMIN_ONLY',
                ],
            ], 403);
        }

        return $next($request);
    }
}
