<?php

namespace App\Http\Requests\Admin;

use App\Models\Subrubro;
use App\Rules\NombreUnico;
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
            'nombre' => ['sometimes', 'string', 'max:100', new NombreUnico(Subrubro::class, ignoreId: (int) $this->route('id'), mensaje: "Ya existe un subrubro con el nombre '{$this->input('nombre')}'.")],
            'permitido_para' => 'sometimes|in:OPERATIVO,ADMIN',
            'afecta_caja' => 'boolean',
        ];
    }
}
