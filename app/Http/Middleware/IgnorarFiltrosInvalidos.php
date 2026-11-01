<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * T109: un filtro inválido escrito a mano en la dirección se ignora, y la pantalla abre
 * como si no estuviera. Las pantallas de Wings solo mandan fechas y textos válidos.
 */
class IgnorarFiltrosInvalidos
{
    /** Los filtros de texto de los listados: ninguno acepta una lista. */
    private const SOLO_TEXTO = ['search', 'q', 'estado', 'fecha'];

    /** Los filtros de rango: siempre vienen de un campo de fecha. */
    private const SOLO_FECHA = ['desde', 'hasta'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            foreach (self::SOLO_TEXTO as $filtro) {
                if (is_array($request->query->all()[$filtro] ?? null)) {
                    $request->query->remove($filtro);
                }
            }

            foreach (self::SOLO_FECHA as $filtro) {
                $valor = $request->query->all()[$filtro] ?? '';
                if ($valor !== '' && !$this->esFecha($valor)) {
                    $request->query->remove($filtro);
                }
            }
        }

        return $next($request);
    }

    private function esFecha(mixed $valor): bool
    {
        return is_string($valor)
            && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $valor, $partes) === 1
            && checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
    }
}
