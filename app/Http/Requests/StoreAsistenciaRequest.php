<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaRequest extends FormRequest
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
            'clase_id' => 'required|exists:clases,id',
            'alumno_id' => 'required|exists:alumnos,id',
            'presente' => 'required|boolean',
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'clase_id.required' => 'Debe seleccionar una clase.',
            'clase_id.exists' => 'La clase seleccionada no existe.',
            'alumno_id.required' => 'Debe seleccionar un alumno.',
            'alumno_id.exists' => 'El alumno seleccionado no existe.',
            'presente.required' => 'Debe indicar si el alumno está presente.',
            'presente.boolean' => 'El campo presente debe ser verdadero o falso.',
        ];
    }
}
