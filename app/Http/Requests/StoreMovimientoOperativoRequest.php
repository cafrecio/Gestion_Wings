<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoOperativoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tipo_caja_id' => 'required|exists:tipos_caja,id',
            'subrubro_id' => 'required|exists:subrubros,id',
            'monto' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:1000',
            'fecha' => 'nullable|date|date_format:Y-m-d',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'tipo_caja_id.required' => 'Debe seleccionar un tipo de caja.',
            'tipo_caja_id.exists' => 'El tipo de caja seleccionado no existe.',

            'subrubro_id.required' => 'Debe seleccionar un subrubro.',
            'subrubro_id.exists' => 'El subrubro seleccionado no existe.',

            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto debe ser mayor a 0.',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',

            'fecha.date' => 'La fecha debe ser válida.',
            'fecha.date_format' => 'La fecha debe tener el formato Y-m-d (ej: 2026-01-25).',
        ];
    }
}
