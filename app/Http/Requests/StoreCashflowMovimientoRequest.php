<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashflowMovimientoRequest extends FormRequest
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
            'subrubro_id' => 'required|exists:subrubros,id',
            'tipo_caja_id' => 'required|exists:tipos_caja,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'nullable|date|date_format:Y-m-d',
            'observaciones' => 'nullable|string|max:1000',
            'referencia_tipo' => 'nullable|string|max:50',
            'referencia_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'subrubro_id.required' => 'Debe seleccionar un subrubro.',
            'subrubro_id.exists' => 'El subrubro seleccionado no existe.',

            'tipo_caja_id.required' => 'Debe seleccionar un tipo de caja.',
            'tipo_caja_id.exists' => 'El tipo de caja seleccionado no existe.',

            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto debe ser mayor a 0.',

            'fecha.date' => 'La fecha debe ser válida.',
            'fecha.date_format' => 'La fecha debe tener el formato Y-m-d (ej: 2026-01-25).',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',

            'referencia_tipo.string' => 'El tipo de referencia debe ser texto.',
            'referencia_tipo.max' => 'El tipo de referencia no puede superar los 50 caracteres.',

            'referencia_id.integer' => 'El ID de referencia debe ser un número entero.',
            'referencia_id.min' => 'El ID de referencia debe ser mayor a 0.',
        ];
    }
}
