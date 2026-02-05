<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeudaCuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alumno_id' => 'required|integer|exists:alumnos,id',
            'periodo' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'monto_original' => 'required|numeric|min:0.01',
        ];
    }

    public function messages(): array
    {
        return [
            'alumno_id.required' => 'El alumno es requerido.',
            'alumno_id.exists' => 'El alumno no existe.',
            'periodo.required' => 'El período es requerido.',
            'periodo.regex' => 'El período debe tener formato YYYY-MM.',
            'monto_original.required' => 'El monto original es requerido.',
            'monto_original.min' => 'El monto debe ser mayor a 0.',
        ];
    }
}
