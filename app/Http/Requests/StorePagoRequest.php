<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
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
            'alumno_id' => 'required|exists:alumnos,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => [
                'required',
                'integer',
                'min:2020',
                'max:' . (Carbon::now()->year + 1),
            ],
            'forma_pago_id' => 'required|exists:formas_pago,id',

            // Fecha de pago (fecha de negocio)
            'fecha_pago' => 'required|date|date_format:Y-m-d',

            // Observaciones opcionales
            'observaciones' => 'nullable|string|max:1000',

            // Campos opcionales para primer pago
            'porcentaje_manual' => 'nullable|numeric|min:0|max:100',
            'regla_primer_pago_id' => 'nullable|exists:reglas_primer_pago,id',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'alumno_id.required' => 'Debe seleccionar un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',

            'mes.required' => 'El mes es obligatorio.',
            'mes.integer' => 'El mes debe ser un número.',
            'mes.min' => 'El mes debe estar entre 1 y 12.',
            'mes.max' => 'El mes debe estar entre 1 y 12.',

            'anio.required' => 'El año es obligatorio.',
            'anio.integer' => 'El año debe ser un número.',
            'anio.min' => 'El año no puede ser anterior a 2020.',
            'anio.max' => 'El año no puede ser mayor al año siguiente.',

            'forma_pago_id.required' => 'Debe seleccionar una forma de pago.',
            'forma_pago_id.exists' => 'La forma de pago seleccionada no existe.',

            'fecha_pago.required' => 'La fecha de pago es obligatoria.',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida.',
            'fecha_pago.date_format' => 'La fecha de pago debe tener el formato Y-m-d (ej: 2026-01-25).',

            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',

            'porcentaje_manual.numeric' => 'El porcentaje debe ser un número.',
            'porcentaje_manual.min' => 'El porcentaje debe ser mayor o igual a 0.',
            'porcentaje_manual.max' => 'El porcentaje debe ser menor o igual a 100.',

            'regla_primer_pago_id.exists' => 'La regla de primer pago seleccionada no existe.',
        ];
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation(): void
    {
        // Si no se proporciona fecha_pago, usar la fecha actual
        if (!$this->has('fecha_pago')) {
            $this->merge(['fecha_pago' => Carbon::now()->format('Y-m-d')]);
        }

        // Si no se proporciona mes/año, usar el actual
        if (!$this->has('mes')) {
            $this->merge(['mes' => Carbon::now()->month]);
        }

        if (!$this->has('anio')) {
            $this->merge(['anio' => Carbon::now()->year]);
        }
    }
}
