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
 * La columna 'nombre' ya usa collation utf8mb4_unicode_ci en MySQL, que es
 * case-insensitive de por sí ('patin' = 'PATIN' es true) pero NO ignora
 * acentos ('patin' = 'patín' es false). Por eso hace falta este paso extra
 * de normalización, no alcanza con un unique de Laravel a secas.
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
            'LOWER(CONVERT(nombre USING utf8mb4)) = ?',
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
