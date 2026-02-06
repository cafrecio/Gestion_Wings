<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rubroId = $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('rubros', 'nombre')->ignore($rubroId)],
            'tipo' => 'sometimes|in:INGRESO,EGRESO',
            'observacion' => 'sometimes|string|max:500',
        ];
    }
}
