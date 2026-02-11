<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolverRevisionCobranzaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accion' => 'required|in:GENERAR_DEUDA,MARCAR_INACTIVO',
        ];
    }

    public function messages(): array
    {
        return [
            'accion.required' => 'Debe indicar la acción a realizar.',
            'accion.in' => 'La acción debe ser GENERAR_DEUDA o MARCAR_INACTIVO.',
        ];
    }
}
