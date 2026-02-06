<?php

namespace App\Http\Requests\Admin;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlumnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $alumnoId = $this->route('id');
        $esMenor = $this->esMenorDeEdad();

        return [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'dni' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('alumnos', 'dni')
                    ->ignore($alumnoId)
                    ->where(fn($query) => $query->where('deporte_id', $this->input('deporte_id'))),
            ],
            'fecha_nacimiento' => 'sometimes|date|before:today',
            'celular' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'deporte_id' => 'sometimes|exists:deportes,id',
            'grupo_id' => 'sometimes|exists:grupos,id',
            'nombre_tutor' => $esMenor ? 'required_with:fecha_nacimiento|string|max:255' : 'nullable',
            'telefono_tutor' => $esMenor ? 'required_with:fecha_nacimiento|string|max:255' : 'nullable',
            'activo' => 'boolean',
        ];
    }

    private function esMenorDeEdad(): bool
    {
        if (!$this->has('fecha_nacimiento')) {
            return false;
        }

        try {
            $fechaNacimiento = Carbon::parse($this->input('fecha_nacimiento'));
            return $fechaNacimiento->diffInYears(Carbon::now()) < 18;
        } catch (\Exception $e) {
            return false;
        }
    }
}
