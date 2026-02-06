<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100|unique:deportes,nombre',
            'tipo_liquidacion' => 'required|in:HORA,COMISION',
            'activo' => 'boolean',
        ];
    }
}
