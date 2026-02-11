<?php

namespace App\Http\Requests\Admin;

use App\Models\Subrubro;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('nombre')) {
                return;
            }

            $nombre = $this->input('nombre');
            $existe = Subrubro::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->exists();

            if ($existe) {
                $validator->errors()->add('nombre', "Ya existe un subrubro con el nombre '{$nombre}'.");
            }
        });
    }
}
