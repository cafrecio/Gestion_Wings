<?php

namespace App\Http\Middleware;

use App\Models\CajaOperativa;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BloqueoCajaViejaOperativo
{
    private const TZ_ARGENTINA = 'America/Argentina/Buenos_Aires';

    /**
     * Handle an incoming request.
     *
     * Bloquea operaciones de escritura si el operativo tiene una caja vieja ABIERTA.
     * Excepción: permite cerrar la propia caja vieja.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = auth()->id();

        // Sin usuario autenticado -> dejar pasar (401 lo maneja auth middleware)
        if (!$userId) {
            return $next($request);
        }

        // ADMIN puede operar sin restricciones
        $user = auth()->user();
        if ($user && $user->rol === 'ADMIN') {
            return $next($request);
        }

        // Buscar caja vieja ABIERTA
        $cajaVieja = $this->buscarCajaViejaAbierta($userId);

        if (!$cajaVieja) {
            // No hay bloqueo
            return $next($request);
        }

        // Excepción: permitir cerrar la propia caja vieja
        if ($this->esCierreDeCajaVieja($request, $cajaVieja->id)) {
            return $next($request);
        }

        // Bloquear con 409 Conflict
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'CAJA_VIEJA_ABIERTA',
                'caja_id' => $cajaVieja->id,
                'mensaje' => 'Tenés una caja abierta de un día anterior. Debés cerrarla antes de operar.',
            ],
        ], 409);
    }

    /**
     * Buscar caja vieja ABIERTA del usuario (día anterior)
     */
    private function buscarCajaViejaAbierta(int $userId): ?CajaOperativa
    {
        $inicioHoyUtc = Carbon::now(self::TZ_ARGENTINA)->startOfDay()->utc();

        return CajaOperativa::where('usuario_operativo_id', $userId)
            ->where('estado', 'ABIERTA')
            ->where('apertura_at', '<', $inicioHoyUtc)
            ->first();
    }

    /**
     * Verificar si el request es para cerrar la caja vieja del usuario
     */
    private function esCierreDeCajaVieja(Request $request, int $cajaViejaId): bool
    {
        // Solo aplica a POST /api/cajas/{id}/cerrar
        if (!$request->isMethod('POST')) {
            return false;
        }

        $path = $request->path();

        // Patrón: api/cajas/{id}/cerrar
        if (preg_match('#^api/cajas/(\d+)/cerrar$#', $path, $matches)) {
            $cajaIdEnRuta = (int) $matches[1];
            return $cajaIdEnRuta === $cajaViejaId;
        }

        return false;
    }
}
