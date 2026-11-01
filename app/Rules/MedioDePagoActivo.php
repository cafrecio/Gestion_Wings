<?php

namespace App\Rules;

use App\Models\TipoCaja;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * T119: un medio inactivo no se ofrece en pantalla y tampoco se acepta por envío directo.
 * Al corregir un movimiento, el medio que ya tenía se puede conservar aunque se haya desactivado después.
 */
final class MedioDePagoActivo implements ValidationRule
{
    public const MENSAJE = 'Elegí un medio de pago activo.';

    public function __construct(private readonly ?int $actual = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->actual !== null && (int) $value === $this->actual) return;
        if (!TipoCaja::whereKey($value)->where('activo', true)->exists()) $fail(self::MENSAJE);
    }
}
