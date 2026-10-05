<?php

namespace App\Services;

/** Criterio del padrón del 21/09, compartido con la primera carga. */
class FormatoExcelCargaService
{
    public static function periodo(mixed $crudo): ?string
    {
        if (is_int($crudo) || (is_float($crudo) && floor($crudo) === $crudo)) {
            $texto = (string) (int) $crudo;
        } elseif (is_string($crudo)) {
            $texto = trim($crudo);
        } else {
            return null;
        }
        if (preg_match('/^\d{5}$/', $texto)) $texto = '0'.$texto;
        if (!preg_match('/^(0[1-9]|1[0-2])(202[5-9]|20[3-9]\d)$/', $texto, $partes)) return null;
        return "{$partes[2]}-{$partes[1]}";
    }

    public static function monto(mixed $crudo): ?string
    {
        if (is_int($crudo) || is_float($crudo)) {
            $importe = (float) $crudo;
        } elseif (is_string($crudo) && preg_match('/^(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{1,2}))?$/', trim($crudo), $partes)) {
            $importe = (float) (str_replace('.', '', $partes[1]).'.'.($partes[2] ?? '0'));
        } else {
            return null;
        }
        return $importe > 0 && is_finite($importe) ? number_format($importe, 2, '.', '') : null;
    }
}
