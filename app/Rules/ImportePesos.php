<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

/** T24: formato de entrada web. Los mínimos/máximos y la obligatoriedad siguen en cada flujo. */
final class ImportePesos implements ValidationRule
{
    public const MENSAJE = 'Ingresá un importe válido, con hasta dos decimales.';

    public static function normalizar(mixed $valor): ?string
    {
        if (!is_string($valor) && !is_int($valor) && !is_float($valor)) return null;
        $texto = (string) $valor;
        // El formato de presentación siempre lleva punto decimal: 48,000.50.
        if (preg_match('/^[0-9]{1,3}(?:,[0-9]{3})+\.[0-9]{1,2}$/D', $texto)) {
            $texto = str_replace(',', '', $texto);
        } else {
            // Una sola coma sin punto es un decimal escrito con el teclado local.
            $texto = str_replace(',', '.', $texto);
        }
        return preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $texto) ? $texto : null;
    }

    /** No corrige ni redondea un pedido inválido: se conserva para que la regla lo rechace. */
    public static function preparar(Request $request, array $campos): void
    {
        $datos = $request->all();
        foreach ($campos as $campo) {
            self::prepararCampo($datos, explode('.', $campo));
        }
        $request->merge($datos);
    }

    private static function prepararCampo(mixed &$datos, array $partes): void
    {
        if ($partes === []) {
            $datos = self::normalizar($datos) ?? $datos;
            return;
        }
        if (!is_array($datos)) return;
        $parte = array_shift($partes);
        if ($parte === '*') {
            foreach ($datos as &$valor) self::prepararCampo($valor, $partes);
        } elseif (array_key_exists($parte, $datos)) {
            self::prepararCampo($datos[$parte], $partes);
        }
    }

    public static function mostrar(mixed $valor): string
    {
        if ($valor === null || $valor === '') return '';
        $normalizado = self::normalizar($valor);
        if ($normalizado === null) return is_scalar($valor) ? (string) $valor : '';
        [$entero, $decimales] = array_pad(explode('.', $normalizado, 2), 2, '');
        $entero = ltrim($entero, '0') ?: '0';
        return preg_replace('/\B(?=(?:[0-9]{3})+(?![0-9]))/', ',', $entero).'.'.str_pad($decimales, 2, '0');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // preparar() convierte solo entradas válidas; nunca recorta decimales por servidor.
        if (!self::esCanonico($value)) {
            $fail(self::MENSAJE);
        }
    }

    public static function esCanonico(mixed $value): bool
    {
        return (is_string($value) || is_int($value) || is_float($value))
            && (bool) preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', (string) $value);
    }
}
