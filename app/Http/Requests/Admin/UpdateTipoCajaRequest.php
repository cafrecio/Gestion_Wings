<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTipoCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tipoCajaId = $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('tipos_caja', 'nombre')->ignore($tipoCajaId)],
            'activo' => 'boolean',
        ];
    }
}
