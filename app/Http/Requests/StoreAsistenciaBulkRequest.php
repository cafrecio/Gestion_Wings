<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.alumno_id' => 'required|exists:alumnos,id',
            'items.*.presente' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe enviar al menos un registro de asistencia.',
            'items.*.alumno_id.required' => 'El alumno_id es requerido.',
            'items.*.alumno_id.exists' => 'El alumno no existe.',
            'items.*.presente.required' => 'El campo presente es requerido.',
            'items.*.presente.boolean' => 'El campo presente debe ser verdadero o falso.',
        ];
    }
}
