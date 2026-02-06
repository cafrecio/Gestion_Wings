<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $deporteId = $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('deportes', 'nombre')->ignore($deporteId)],
            'tipo_liquidacion' => 'sometimes|in:HORA,COMISION',
            'activo' => 'boolean',
        ];
    }
}
