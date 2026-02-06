<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRubroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100|unique:rubros,nombre',
            'tipo' => 'required|in:INGRESO,EGRESO',
            'observacion' => 'required|string|max:500',
        ];
    }
}
