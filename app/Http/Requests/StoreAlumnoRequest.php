<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlumnoRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $esMenor = $this->esMenorDeEdad();

        return [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before:today',
            'celular' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'deporte_id' => 'required|exists:deportes,id',
            'grupo_id' => 'required|exists:grupos,id',
            
            // Validaciones condicionales para tutor
            'nombre_tutor' => $esMenor ? 'required|string|max:255' : 'nullable',
            'telefono_tutor' => $esMenor ? 'required|string|max:255' : 'nullable',
        ];
    }

    /**
     * Calcular si es menor de edad basado en fecha_nacimiento
     */
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

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'celular.required' => 'El celular es obligatorio.',
            'deporte_id.required' => 'Debe seleccionar un deporte.',
            'deporte_id.exists' => 'El deporte seleccionado no existe.',
            'grupo_id.required' => 'Debe seleccionar un grupo.',
            'grupo_id.exists' => 'El grupo seleccionado no existe.',
            'nombre_tutor.required' => 'El nombre del tutor es obligatorio para menores de edad.',
            'telefono_tutor.required' => 'El teléfono del tutor es obligatorio para menores de edad.',
            'email.email' => 'El email debe ser una dirección válida.',
        ];
    }
}
