<?php

namespace App\Http\Requests\Admin;

use App\Models\Subrubro;
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('nombre') || !$this->has('nombre')) {
                return;
            }

            $nombre = $this->input('nombre');
            $subrubroId = $this->route('id');
            $existe = Subrubro::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])
                ->where('id', '!=', $subrubroId)
                ->exists();

            if ($existe) {
                $validator->errors()->add('nombre', "Ya existe un subrubro con el nombre '{$nombre}'.");
            }
        });
    }
}
