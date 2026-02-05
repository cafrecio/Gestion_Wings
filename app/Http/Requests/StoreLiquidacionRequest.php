<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLiquidacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profesor_id' => 'required|integer|exists:profesores,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2020|max:2100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'profesor_id.required' => 'El profesor es obligatorio.',
            'profesor_id.exists' => 'El profesor no existe.',
            'mes.required' => 'El mes es obligatorio.',
            'mes.min' => 'El mes debe ser entre 1 y 12.',
            'mes.max' => 'El mes debe ser entre 1 y 12.',
            'anio.required' => 'El año es obligatorio.',
            'anio.min' => 'El año debe ser mayor a 2020.',
            'anio.max' => 'El año debe ser menor a 2100.',
        ];
    }
}
