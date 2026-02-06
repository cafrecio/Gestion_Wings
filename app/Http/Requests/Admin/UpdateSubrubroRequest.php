<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubrubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rubro_id' => 'sometimes|exists:rubros,id',
            'nombre' => 'sometimes|string|max:100',
            'permitido_para' => 'sometimes|in:OPERATIVO,ADMIN',
            'afecta_caja' => 'boolean',
        ];
    }
}
