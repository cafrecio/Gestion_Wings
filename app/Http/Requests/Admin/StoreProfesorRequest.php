<?php

namespace App\Http\Requests\Admin;

use App\Models\Deporte;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deporte_id' => 'required|exists:deportes,id',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'valor_hora' => 'nullable|numeric|min:0',
            'porcentaje_comision' => 'nullable|numeric|min:0|max:100',
            'activo' => 'boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $deporteId = $this->input('deporte_id');
            if (!$deporteId) {
                return;
            }

            $deporte = Deporte::find($deporteId);
            if (!$deporte) {
                return;
            }

            if ($deporte->tipo_liquidacion === 'HORA') {
                if (empty($this->input('valor_hora'))) {
                    $validator->errors()->add('valor_hora', 'El valor por hora es requerido para deportes con liquidación por HORA.');
                }
            }

            if ($deporte->tipo_liquidacion === 'COMISION') {
                if (empty($this->input('porcentaje_comision'))) {
                    $validator->errors()->add('porcentaje_comision', 'El porcentaje de comisión es requerido para deportes con liquidación por COMISION.');
                }
            }
        });
    }
}
