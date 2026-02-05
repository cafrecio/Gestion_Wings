<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarProfesorRequest extends FormRequest
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
            'profesor_id' => 'required|exists:profesores,id',
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
            'profesor_id.required' => 'Debe seleccionar un profesor.',
            'profesor_id.exists' => 'El profesor seleccionado no existe.',
        ];
    }
}
