<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoCuotaOperativoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alumno_id' => 'required|integer|exists:alumnos,id',
            'tipo_caja_id' => 'required|integer|exists:tipos_caja,id',
            'fecha_pago' => 'nullable|date|date_format:Y-m-d',
            'observaciones' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'items.*.monto' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'alumno_id.required' => 'El alumno es requerido.',
            'alumno_id.exists' => 'El alumno no existe.',
            'tipo_caja_id.required' => 'El tipo de caja es requerido.',
            'tipo_caja_id.exists' => 'El tipo de caja no existe.',
            'items.required' => 'Debe incluir al menos un período a pagar.',
            'items.*.periodo.required' => 'El período es requerido.',
            'items.*.periodo.regex' => 'El período debe tener formato YYYY-MM.',
            'items.*.monto.required' => 'El monto es requerido para cada período.',
            'items.*.monto.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
