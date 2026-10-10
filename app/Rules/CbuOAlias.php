<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un CBU o CVU (22 numeros) o un alias (6 a 20 letras, numeros, puntos o guiones).
 *
 * No verifica que la cuenta exista ni el digito verificador: solo evita guardar algo que
 * a simple vista no es ninguna de las dos cosas, que es el error tipico al copiar a mano.
 *
 * Es la unica regla del campo, a proposito: todo rechazo sale con el mismo mensaje en
 * castellano. Con `max` o `string` al lado, un texto largo se rechazaba en ingles.
 */
class CbuOAlias implements ValidationRule
{
    public const MENSAJE = 'Tiene que ser un CBU o CVU de 22 números, o un alias de 6 a 20 letras, números, puntos o guiones.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        // Un arreglo mandado por POST directo no es un valor: se rechaza, no se ignora.
        if (! is_scalar($value)) {
            $fail(self::MENSAJE);

            return;
        }

        $limpio = self::normalizar((string) $value);
        if ($limpio === null) {
            return;
        }

        $esCbu = (bool) preg_match('/^\d{22}$/D', $limpio);
        $esAlias = (bool) preg_match('/^[A-Za-z0-9.\-]{6,20}$/D', $limpio) && ! ctype_digit($limpio);

        if (! $esCbu && ! $esAlias) {
            $fail(self::MENSAJE);
        }
    }

    /**
     * Como se guarda. Los espacios de adentro se sacan SOLO si lo demas son numeros, que
     * es como suele venir pegado un CBU («0170 0992 ...»). A un alias no se le toca nada:
     * «mi alias» no es «mialias», es un dato mal escrito y tiene que rechazarse.
     */
    public static function normalizar(mixed $valor): ?string
    {
        if (! is_scalar($valor)) {
            return null;
        }
        $texto = trim((string) $valor);
        if ($texto !== '' && preg_match('/^[\d\s]+$/', $texto)) {
            $texto = preg_replace('/\s+/', '', $texto);
        }

        return $texto === '' ? null : $texto;
    }
}
