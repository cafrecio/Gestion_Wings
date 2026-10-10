<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un CBU o CVU (22 numeros) o un alias (6 a 20 letras, numeros, puntos o guiones).
 *
 * No verifica que la cuenta exista ni el digito verificador: solo evita guardar algo que
 * a simple vista no es ninguna de las dos cosas, que es el error tipico al copiar a mano.
 */
class CbuOAlias implements ValidationRule
{
    public const MENSAJE = 'Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $limpio = self::normalizar(is_scalar($value) ? (string) $value : null);
        if ($limpio === null) {
            return;
        }

        $esCbu = (bool) preg_match('/^\d{22}$/D', $limpio);
        $esAlias = (bool) preg_match('/^[A-Za-z0-9.\-]{6,20}$/D', $limpio) && ! ctype_digit($limpio);

        if (! $esCbu && ! $esAlias) {
            $fail(self::MENSAJE);
        }
    }

    /** Como se guarda: sin espacios, que es como suele venir pegado un CBU. */
    public static function normalizar(?string $valor): ?string
    {
        $limpio = preg_replace('/\s+/u', '', (string) $valor);

        return $limpio === '' ? null : $limpio;
    }
}
