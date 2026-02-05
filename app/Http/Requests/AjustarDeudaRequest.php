<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustarDeudaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nuevo_monto' => 'required|numeric|min:0',
            'observaciones' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nuevo_monto.required' => 'El nuevo monto es obligatorio.',
            'nuevo_monto.min' => 'El monto no puede ser negativo.',
            'observaciones.required' => 'Las observaciones son obligatorias para ajustar una deuda.',
            'observaciones.min' => 'Las observaciones deben tener al menos 10 caracteres.',
        ];
    }
}
