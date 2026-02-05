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
                    'message' => 'Debe iniciar sesión para acceder a este recurso.',
                ],
            ], 401);
        }

        // Verificar rol admin (soporta ambos nombres: role y rol)
        $isAdmin = ($user->role ?? null) === 'ADMIN'
                || ($user->rol ?? null) === 'ADMIN';

        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN_ADMIN_ONLY',
                    'message' => 'Este recurso requiere permisos de administrador.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
