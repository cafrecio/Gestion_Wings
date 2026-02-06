<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubrubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rubro_id' => 'required|exists:rubros,id',
            'nombre' => 'required|string|max:100',
            'permitido_para' => 'required|in:OPERATIVO,ADMIN',
            'afecta_caja' => 'boolean',
            'es_reservado_sistema' => 'boolean',
        ];
    }
}
