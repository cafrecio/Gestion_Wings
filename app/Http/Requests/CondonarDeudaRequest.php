<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CondonarDeudaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observaciones' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'observaciones.required' => 'Las observaciones son obligatorias para condonar una deuda.',
            'observaciones.min' => 'Las observaciones deben tener al menos 10 caracteres.',
        ];
    }
}
