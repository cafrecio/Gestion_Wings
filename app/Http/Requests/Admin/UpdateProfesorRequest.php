<?php

namespace App\Http\Requests\Admin;

use App\Models\Deporte;
use App\Models\Profesor;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deporte_id' => 'sometimes|exists:deportes,id',
            'nombre' => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
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
            $profesorId = $this->route('id');
            $profesor = Profesor::find($profesorId);
            if (!$profesor) {
                return;
            }

            $deporteId = $this->input('deporte_id', $profesor->deporte_id);
            $deporte = Deporte::find($deporteId);
            if (!$deporte) {
                return;
            }

            $valorHora = $this->has('valor_hora') ? $this->input('valor_hora') : $profesor->valor_hora;
            $porcentaje = $this->has('porcentaje_comision') ? $this->input('porcentaje_comision') : $profesor->porcentaje_comision;

            if ($deporte->tipo_liquidacion === 'HORA' && empty($valorHora)) {
                $validator->errors()->add('valor_hora', 'El valor por hora es requerido para deportes con liquidación por HORA.');
            }

            if ($deporte->tipo_liquidacion === 'COMISION' && empty($porcentaje)) {
                $validator->errors()->add('porcentaje_comision', 'El porcentaje de comisión es requerido para deportes con liquidación por COMISION.');
            }
        });
    }
}
