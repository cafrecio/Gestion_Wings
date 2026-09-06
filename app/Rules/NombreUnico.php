<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * B5 — única fuente de verdad para "no permitir nombres duplicados"
 * ignorando mayúsculas y acentos. Antes esta lógica estaba copiada (con
 * variaciones entre sí) en NivelWebController, TipoCajaWebController y
 * Store/UpdateSubrubroRequest.
 *
 * En MariaDB la colación utf8mb4_unicode_ci de 'nombre' aporta también
 * la equivalencia de acentos. SQLite no reproduce esa colación: los tests
 * de acentos requieren MariaDB (B2). Se conserva la normalización de entrada.
 */
class NombreUnico implements ValidationRule
{
    public function __construct(
        private readonly string $modelClass,
        private readonly ?int $ignoreId = null,
        private readonly string $mensaje = 'Ya existe un registro con un nombre similar.',
    ) {
    }

    public static function normalizar(string $nombre): string
    {
        $nombre = mb_strtolower(trim($nombre));
        $nombre = strtr($nombre, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'à' => 'a', 'è' => 'e', 'ì' => 'i',
            'ò' => 'o', 'ù' => 'u',
        ]);

        return preg_replace('/\s+/', ' ', $nombre);
    }

    public static function existe(string $modelClass, string $nombre, ?int $ignoreId = null): bool
    {
        $query = $modelClass::whereRaw(
            'LOWER(nombre) = ?',
            [self::normalizar($nombre)]
        );

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::existe($this->modelClass, (string) $value, $this->ignoreId)) {
            $fail($this->mensaje);
        }
    }
}
