<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PagarLiquidacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha_pago' => 'nullable|date|date_format:Y-m-d',
            'tipo_caja_id' => 'required|integer|exists:tipos_caja,id',
            'subrubro_id' => 'required|integer|exists:subrubros,id',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_caja_id.required' => 'El tipo de caja es obligatorio.',
            'tipo_caja_id.exists' => 'El tipo de caja no existe.',
            'subrubro_id.required' => 'El subrubro es obligatorio.',
            'subrubro_id.exists' => 'El subrubro no existe.',
            'fecha_pago.date_format' => 'La fecha debe tener formato YYYY-MM-DD.',
        ];
    }
}
