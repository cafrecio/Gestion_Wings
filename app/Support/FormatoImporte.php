<?php

namespace App\Support;

/** T30: presentación de pesos; no valida entradas ni decide redondeos de negocio. */
final class FormatoImporte
{
    private const DECIMALES = 2;
    private const DECIMAL = '.';
    private const MILES = ',';
    private const MONEDA = '$';

    /** Misma política para PHP, los campos T24 y el código de presentación del navegador. */
    public static function configuracion(): array
    {
        return ['locale' => 'en-US', 'decimales' => self::DECIMALES,
            'decimal' => self::DECIMAL, 'miles' => self::MILES, 'moneda' => self::MONEDA];
    }

    public static function numero(float|int|string|null $importe): string
    {
        $texto = (string) ($importe ?? 0);
        // DECIMAL de la base y enteros: no pasar por float ni perder dígitos/centavos.
        if (preg_match('/^(-?)([0-9]+)(?:\.([0-9]{1,2}))?$/D', $texto, $partes)) {
            $entero = ltrim($partes[2], '0') ?: '0';
            $fraccion = str_pad($partes[3] ?? '', self::DECIMALES, '0');
            $signo = $partes[1] === '-' && ($entero !== '0' || trim($fraccion, '0') !== '') ? '-' : '';
            return $signo.preg_replace('/\B(?=(?:[0-9]{3})+(?![0-9]))/', self::MILES, $entero)
                .self::DECIMAL.$fraccion;
        }
        // Resultados de cálculos existentes: cambia solo su presentación a dos decimales.
        return number_format((float) $importe, self::DECIMALES, self::DECIMAL, self::MILES);
    }

    public static function pesos(float|int|string|null $importe): string
    {
        return self::MONEDA.self::numero($importe);
    }

    /** Descripciones históricas de liquidación: se cambia su lectura, no el dato guardado. */
    public static function desdeArgentino(string $importe): string
    {
        return self::pesos(str_replace(['.', ','], ['', '.'], $importe));
    }

    public static function textoHistorico(string $texto): string
    {
        return preg_replace_callback('/\$\s*([0-9]+(?:\.[0-9]{3})*(?:,[0-9]{1,2})?)/',
            fn ($partes) => self::desdeArgentino($partes[1]), $texto);
    }

    /** Código de formato numérico de Excel; sus separadores dependen del lector. */
    public static function excel(): string
    {
        return '#,##0'.self::DECIMAL.str_repeat('0', self::DECIMALES);
    }
}
