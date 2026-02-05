<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\ClaseProfesor;
use App\Models\Profesor;
use App\Models\Alumno;
use Carbon\Carbon;

class ClaseService
{
    /**
     * Crear una nueva clase
     *
     * La duración default es 1 hora (desde hora_inicio), pero es editable.
     * Un mismo grupo puede tener más de una clase en el mismo día y horario.
     * No se valida solapamiento por grupo.
     *
     * @param int $grupoId
     * @param string $fecha (Y-m-d)
     * @param string $horaInicio (H:i o H:i:s)
     * @param string|null $horaFin (H:i o H:i:s) - Si no se provee, se calcula como hora_inicio + 1 hora
     * @return Clase
     */
    public function crearClase(
        int $grupoId,
        string $fecha,
        string $horaInicio,
        ?string $horaFin = null
    ): Clase {
        $clase = Clase::create([
            'grupo_id' => $grupoId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin, // El modelo calcula el default si es null
        ]);

        return $clase;
    }

    /**
     * Asignar un profesor a una clase
     *
     * Validación: Un profesor NO puede estar asignado a dos clases que se solapen
     * en fecha + horario.
     *
     * @param int $claseId
     * @param int $profesorId
     * @return ClaseProfesor
     * @throws \Exception Si el profesor ya tiene una clase solapada
     */
    public function asignarProfesor(int $claseId, int $profesorId): ClaseProfesor
    {
        $clase = Clase::findOrFail($claseId);
        $profesor = Profesor::findOrFail($profesorId);

        // Validar solapamiento: el profesor no puede estar en dos clases al mismo tiempo
        $this->validarSolapamientoProfesor($profesor, $clase);

        // Verificar si ya está asignado
        $existente = ClaseProfesor::where('clase_id', $claseId)
            ->where('profesor_id', $profesorId)
            ->first();

        if ($existente) {
            throw new \Exception('El profesor ya está asignado a esta clase.');
        }

        $claseProfesor = ClaseProfesor::create([
            'clase_id' => $claseId,
            'profesor_id' => $profesorId,
        ]);

        return $claseProfesor;
    }

    /**
     * Registrar asistencia de un alumno a una clase
     *
     * Validación: Un alumno no puede asistir a dos clases que se solapen
     * en fecha + horario. Esto aplica incluso si son del mismo grupo.
     *
     * @param int $claseId
     * @param int $alumnoId
     * @param bool $presente
     * @return Asistencia
     * @throws \Exception Si el alumno tiene otra asistencia solapada con presente=true
     */
    public function registrarAsistencia(int $claseId, int $alumnoId, bool $presente): Asistencia
    {
        $clase = Clase::findOrFail($claseId);
        $alumno = Alumno::findOrFail($alumnoId);

        // Si se marca como presente, validar solapamiento
        if ($presente) {
            $this->validarSolapamientoAlumno($alumno, $clase);
        }

        // Verificar si ya existe asistencia para esta clase/alumno
        $asistenciaExistente = Asistencia::where('clase_id', $claseId)
            ->where('alumno_id', $alumnoId)
            ->first();

        if ($asistenciaExistente) {
            // Actualizar asistencia existente
            // Si cambia de ausente a presente, validar solapamiento
            if ($presente && !$asistenciaExistente->presente) {
                $this->validarSolapamientoAlumno($alumno, $clase, $asistenciaExistente->id);
            }

            $asistenciaExistente->update(['presente' => $presente]);
            return $asistenciaExistente;
        }

        $asistencia = Asistencia::create([
            'clase_id' => $claseId,
            'alumno_id' => $alumnoId,
            'presente' => $presente,
        ]);

        return $asistencia;
    }

    /**
     * Validar que un profesor no tenga clases solapadas
     *
     * @param Profesor $profesor
     * @param Clase $claseNueva
     * @param int|null $excluirClaseId - ID de clase a excluir (para updates)
     * @throws \Exception
     */
    private function validarSolapamientoProfesor(
        Profesor $profesor,
        Clase $claseNueva,
        ?int $excluirClaseId = null
    ): void {
        // Obtener todas las clases del profesor en la misma fecha
        $clasesDelProfesor = $profesor->clases()
            ->where('fecha', $claseNueva->fecha->format('Y-m-d'))
            ->when($excluirClaseId, function ($query) use ($excluirClaseId) {
                return $query->where('clases.id', '!=', $excluirClaseId);
            })
            ->get();

        foreach ($clasesDelProfesor as $claseExistente) {
            if ($claseExistente->seSolapaCon(
                $claseNueva->fecha->format('Y-m-d'),
                $claseNueva->hora_inicio->format('H:i:s'),
                $claseNueva->hora_fin->format('H:i:s')
            )) {
                throw new \Exception(
                    "El profesor {$profesor->nombre_completo} ya tiene asignada otra clase " .
                    "que se solapa en fecha {$claseNueva->fecha->format('Y-m-d')} " .
                    "entre {$claseExistente->hora_inicio->format('H:i')} y {$claseExistente->hora_fin->format('H:i')}."
                );
            }
        }
    }

    /**
     * Validar que un alumno no tenga asistencias solapadas (con presente=true)
     *
     * @param Alumno $alumno
     * @param Clase $claseNueva
     * @param int|null $excluirAsistenciaId - ID de asistencia a excluir (para updates)
     * @throws \Exception
     */
    private function validarSolapamientoAlumno(
        Alumno $alumno,
        Clase $claseNueva,
        ?int $excluirAsistenciaId = null
    ): void {
        // Obtener todas las asistencias del alumno donde estuvo presente en la misma fecha
        $asistenciasDelAlumno = Asistencia::where('alumno_id', $alumno->id)
            ->where('presente', true)
            ->when($excluirAsistenciaId, function ($query) use ($excluirAsistenciaId) {
                return $query->where('id', '!=', $excluirAsistenciaId);
            })
            ->whereHas('clase', function ($query) use ($claseNueva) {
                $query->where('fecha', $claseNueva->fecha->format('Y-m-d'));
            })
            ->with('clase')
            ->get();

        foreach ($asistenciasDelAlumno as $asistencia) {
            $claseExistente = $asistencia->clase;

            if ($claseExistente->seSolapaCon(
                $claseNueva->fecha->format('Y-m-d'),
                $claseNueva->hora_inicio->format('H:i:s'),
                $claseNueva->hora_fin->format('H:i:s')
            )) {
                throw new \Exception(
                    "El alumno {$alumno->nombre} {$alumno->apellido} ya tiene registrada asistencia " .
                    "a otra clase que se solapa en fecha {$claseNueva->fecha->format('Y-m-d')} " .
                    "entre {$claseExistente->hora_inicio->format('H:i')} y {$claseExistente->hora_fin->format('H:i')}."
                );
            }
        }
    }

    /**
     * Verificar si un profesor puede ser asignado a una clase (sin crear la asignación)
     *
     * @param int $claseId
     * @param int $profesorId
     * @return array [puede_asignar: bool, razon: string|null]
     */
    public function verificarDisponibilidadProfesor(int $claseId, int $profesorId): array
    {
        try {
            $clase = Clase::findOrFail($claseId);
            $profesor = Profesor::findOrFail($profesorId);

            $this->validarSolapamientoProfesor($profesor, $clase);

            return [
                'puede_asignar' => true,
                'razon' => null,
            ];
        } catch (\Exception $e) {
            return [
                'puede_asignar' => false,
                'razon' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar si un alumno puede asistir a una clase (sin crear la asistencia)
     *
     * @param int $claseId
     * @param int $alumnoId
     * @return array [puede_asistir: bool, razon: string|null]
     */
    public function verificarDisponibilidadAlumno(int $claseId, int $alumnoId): array
    {
        try {
            $clase = Clase::findOrFail($claseId);
            $alumno = Alumno::findOrFail($alumnoId);

            $this->validarSolapamientoAlumno($alumno, $clase);

            return [
                'puede_asistir' => true,
                'razon' => null,
            ];
        } catch (\Exception $e) {
            return [
                'puede_asistir' => false,
                'razon' => $e->getMessage(),
            ];
        }
    }
}
