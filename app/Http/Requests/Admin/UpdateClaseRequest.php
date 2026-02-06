<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupo_id' => 'sometimes|exists:grupos,id',
            'fecha' => 'sometimes|date',
            'hora_inicio' => 'sometimes|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'profesor_ids' => 'nullable|array',
            'profesor_ids.*' => 'exists:profesores,id',
            'cancelada' => 'boolean',
            'validada_para_liquidacion' => 'boolean',
        ];
    }
}
