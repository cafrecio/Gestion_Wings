<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Deporte;
use App\Models\Liquidacion;
use App\Models\LiquidacionDetalle;
use App\Models\Pago;
use App\Models\Profesor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LiquidacionService
{
    /**
     * Generar liquidación mensual para un profesor
     *
     * @param int $profesorId
     * @param int $mes (1-12)
     * @param int $anio
     * @return Liquidacion
     * @throws \Exception
     */
    public function generarLiquidacionMensual(int $profesorId, int $mes, int $anio): Liquidacion
    {
        $profesor = Profesor::with('deporte')->findOrFail($profesorId);

        $this->validarProfesorParaLiquidacion($profesor);
        $this->validarNoExisteLiquidacion($profesorId, $mes, $anio);

        $tipoLiquidacion = $profesor->tipo_liquidacion;

        return DB::transaction(function () use ($profesor, $mes, $anio, $tipoLiquidacion) {
            $liquidacion = Liquidacion::create([
                'profesor_id' => $profesor->id,
                'mes' => $mes,
                'anio' => $anio,
                'tipo' => $tipoLiquidacion,
                'total_calculado' => 0,
                'estado' => Liquidacion::ESTADO_ABIERTA,
            ]);

            if ($tipoLiquidacion === Deporte::TIPO_LIQUIDACION_HORA) {
                $total = $this->calcularLiquidacionHora($liquidacion, $profesor, $mes, $anio);
            } else {
                $total = $this->calcularLiquidacionComision($liquidacion, $profesor, $mes, $anio);
            }

            $liquidacion->update(['total_calculado' => $total]);

            return $liquidacion->fresh(['detalles', 'profesor']);
        });
    }

    /**
     * Calcular liquidación por HORA
     * Se liquidan clases con asistencia o validadas manualmente
     * Cada profesor cobra su valor_hora completo por cada clase
     *
     * @param Liquidacion $liquidacion
     * @param Profesor $profesor
     * @param int $mes
     * @param int $anio
     * @return float
     */
    public function calcularLiquidacionHora(
        Liquidacion $liquidacion,
        Profesor $profesor,
        int $mes,
        int $anio
    ): float {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        $clasesDelProfesor = Clase::whereHas('profesores', function ($query) use ($profesor) {
            $query->where('profesores.id', $profesor->id);
        })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('cancelada', false)
            ->get();

        $total = 0;
        $valorHora = $profesor->valor_hora ?? 0;

        foreach ($clasesDelProfesor as $clase) {
            if (!$clase->esLiquidable()) {
                continue;
            }

            $monto = $valorHora;

            LiquidacionDetalle::create([
                'liquidacion_id' => $liquidacion->id,
                'tipo_referencia' => LiquidacionDetalle::TIPO_CLASE,
                'referencia_id' => $clase->id,
                'monto' => $monto,
                'descripcion' => sprintf(
                    'Clase %s - %s (%s)',
                    $clase->fecha->format('d/m/Y'),
                    $clase->grupo->nombre ?? 'Sin grupo',
                    $clase->validada_para_liquidacion ? 'Validada manual' : 'Con asistencia'
                ),
            ]);

            $total += $monto;
        }

        return $total;
    }

    /**
     * Calcular liquidación por COMISION
     * Se liquidan alumnos del deporte que:
     * - Pagaron en el mes
     * - Asistieron al menos una clase dictada por ESTE profesor en el mes
     *
     * IMPORTANTE: Si un alumno asistió a clases de múltiples profesores,
     * cada profesor cobra su comisión completa sobre el pago del alumno.
     *
     * @param Liquidacion $liquidacion
     * @param Profesor $profesor
     * @param int $mes
     * @param int $anio
     * @return float
     */
    public function calcularLiquidacionComision(
        Liquidacion $liquidacion,
        Profesor $profesor,
        int $mes,
        int $anio
    ): float {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        $deporteId = $profesor->deporte_id;
        $porcentajeComision = $profesor->porcentaje_comision ?? 0;

        $alumnosConPago = Pago::where('mes', $mes)
            ->where('anio', $anio)
            ->where('estado', 'pagado')
            ->whereHas('alumno', function ($query) use ($deporteId) {
                $query->where('deporte_id', $deporteId)
                    ->where('activo', true);
            })
            ->with('alumno')
            ->get();

        $total = 0;

        foreach ($alumnosConPago as $pago) {
            $alumno = $pago->alumno;

            // Verificar si el alumno asistió a al menos una clase de ESTE profesor
            $tieneAsistenciaConProfesor = Asistencia::where('alumno_id', $alumno->id)
                ->where('presente', true)
                ->whereHas('clase', function ($query) use ($fechaInicio, $fechaFin, $profesor) {
                    $query->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->where('cancelada', false)
                        ->whereHas('profesores', function ($q) use ($profesor) {
                            $q->where('profesores.id', $profesor->id);
                        });
                })
                ->exists();

            if (!$tieneAsistenciaConProfesor) {
                continue;
            }

            $montoComision = round($pago->monto_final * ($porcentajeComision / 100), 2);

            LiquidacionDetalle::create([
                'liquidacion_id' => $liquidacion->id,
                'tipo_referencia' => LiquidacionDetalle::TIPO_ALUMNO,
                'referencia_id' => $alumno->id,
                'monto' => $montoComision,
                'descripcion' => sprintf(
                    'Comisión %s %s - Pago $%s (%s%%)',
                    $alumno->nombre,
                    $alumno->apellido,
                    number_format($pago->monto_final, 2, ',', '.'),
                    $porcentajeComision
                ),
            ]);

            $total += $montoComision;
        }

        return $total;
    }

    /**
     * Cerrar una liquidación (hacerla inmutable)
     *
     * @param int $liquidacionId
     * @return Liquidacion
     * @throws \Exception
     */
    public function cerrarLiquidacion(int $liquidacionId): Liquidacion
    {
        $liquidacion = Liquidacion::findOrFail($liquidacionId);

        if ($liquidacion->estaCerrada()) {
            throw new \Exception('La liquidación ya está cerrada.');
        }

        $liquidacion->update(['estado' => Liquidacion::ESTADO_CERRADA]);

        return $liquidacion->fresh();
    }

    /**
     * Recalcular una liquidación abierta
     * Elimina los detalles existentes y recalcula
     *
     * @param int $liquidacionId
     * @return Liquidacion
     * @throws \Exception
     */
    public function recalcularLiquidacion(int $liquidacionId): Liquidacion
    {
        $liquidacion = Liquidacion::with('profesor.deporte')->findOrFail($liquidacionId);

        if ($liquidacion->estaCerrada()) {
            throw new \Exception('No se puede recalcular una liquidación cerrada.');
        }

        return DB::transaction(function () use ($liquidacion) {
            $liquidacion->detalles()->delete();

            $profesor = $liquidacion->profesor;

            if ($liquidacion->tipo === Liquidacion::TIPO_HORA) {
                $total = $this->calcularLiquidacionHora(
                    $liquidacion,
                    $profesor,
                    $liquidacion->mes,
                    $liquidacion->anio
                );
            } else {
                $total = $this->calcularLiquidacionComision(
                    $liquidacion,
                    $profesor,
                    $liquidacion->mes,
                    $liquidacion->anio
                );
            }

            $liquidacion->update(['total_calculado' => $total]);

            return $liquidacion->fresh(['detalles', 'profesor']);
        });
    }

    /**
     * Obtener liquidaciones de un profesor
     *
     * @param int $profesorId
     * @param int|null $anio
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerLiquidacionesProfesor(int $profesorId, ?int $anio = null)
    {
        $query = Liquidacion::where('profesor_id', $profesorId)
            ->with('detalles')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc');

        if ($anio) {
            $query->where('anio', $anio);
        }

        return $query->get();
    }

    /**
     * Obtener resumen de liquidaciones de un período
     *
     * @param int $mes
     * @param int $anio
     * @return array
     */
    public function obtenerResumenPeriodo(int $mes, int $anio): array
    {
        $liquidaciones = Liquidacion::periodo($mes, $anio)
            ->with('profesor')
            ->get();

        return [
            'periodo' => sprintf('%02d/%d', $mes, $anio),
            'total_liquidaciones' => $liquidaciones->count(),
            'total_monto' => $liquidaciones->sum('total_calculado'),
            'abiertas' => $liquidaciones->where('estado', Liquidacion::ESTADO_ABIERTA)->count(),
            'cerradas' => $liquidaciones->where('estado', Liquidacion::ESTADO_CERRADA)->count(),
            'por_tipo' => [
                'HORA' => $liquidaciones->where('tipo', Liquidacion::TIPO_HORA)->sum('total_calculado'),
                'COMISION' => $liquidaciones->where('tipo', Liquidacion::TIPO_COMISION)->sum('total_calculado'),
            ],
            'liquidaciones' => $liquidaciones,
        ];
    }

    /**
     * Validar que el profesor tiene los datos necesarios para liquidar
     *
     * @param Profesor $profesor
     * @throws \Exception
     */
    private function validarProfesorParaLiquidacion(Profesor $profesor): void
    {
        if (!$profesor->deporte_id) {
            throw new \Exception('El profesor no tiene un deporte asignado.');
        }

        if (!$profesor->deporte) {
            throw new \Exception('El deporte del profesor no existe.');
        }

        $tipoLiquidacion = $profesor->tipo_liquidacion;

        if ($tipoLiquidacion === Deporte::TIPO_LIQUIDACION_HORA) {
            if ($profesor->valor_hora === null || $profesor->valor_hora <= 0) {
                throw new \Exception('El profesor no tiene valor por hora configurado.');
            }
        }

        if ($tipoLiquidacion === Deporte::TIPO_LIQUIDACION_COMISION) {
            if ($profesor->porcentaje_comision === null || $profesor->porcentaje_comision <= 0) {
                throw new \Exception('El profesor no tiene porcentaje de comisión configurado.');
            }
        }
    }

    /**
     * Validar que no exista liquidación para el período
     *
     * @param int $profesorId
     * @param int $mes
     * @param int $anio
     * @throws \Exception
     */
    private function validarNoExisteLiquidacion(int $profesorId, int $mes, int $anio): void
    {
        $existe = Liquidacion::where('profesor_id', $profesorId)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->exists();

        if ($existe) {
            throw new \Exception(
                sprintf('Ya existe una liquidación para el profesor en %02d/%d.', $mes, $anio)
            );
        }
    }

    /**
     * Eliminar una liquidación abierta
     *
     * @param int $liquidacionId
     * @throws \Exception
     */
    public function eliminarLiquidacion(int $liquidacionId): void
    {
        $liquidacion = Liquidacion::findOrFail($liquidacionId);

        if ($liquidacion->estaCerrada()) {
            throw new \Exception('No se puede eliminar una liquidación cerrada.');
        }

        $liquidacion->delete();
    }

    /**
     * Obtener vista previa de liquidación sin guardar
     *
     * @param int $profesorId
     * @param int $mes
     * @param int $anio
     * @return array
     * @throws \Exception
     */
    public function previsualizarLiquidacion(int $profesorId, int $mes, int $anio): array
    {
        $profesor = Profesor::with('deporte')->findOrFail($profesorId);

        $this->validarProfesorParaLiquidacion($profesor);

        $tipoLiquidacion = $profesor->tipo_liquidacion;
        $detalles = [];
        $total = 0;

        if ($tipoLiquidacion === Deporte::TIPO_LIQUIDACION_HORA) {
            $resultado = $this->previsualizarLiquidacionHora($profesor, $mes, $anio);
        } else {
            $resultado = $this->previsualizarLiquidacionComision($profesor, $mes, $anio);
        }

        return [
            'profesor' => [
                'id' => $profesor->id,
                'nombre_completo' => $profesor->nombre_completo,
                'deporte' => $profesor->deporte->nombre,
            ],
            'periodo' => sprintf('%02d/%d', $mes, $anio),
            'tipo' => $tipoLiquidacion,
            'total_estimado' => $resultado['total'],
            'detalles' => $resultado['detalles'],
        ];
    }

    /**
     * Previsualizar liquidación por hora
     */
    private function previsualizarLiquidacionHora(Profesor $profesor, int $mes, int $anio): array
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        $clasesDelProfesor = Clase::whereHas('profesores', function ($query) use ($profesor) {
            $query->where('profesores.id', $profesor->id);
        })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('cancelada', false)
            ->with('grupo')
            ->get();

        $detalles = [];
        $total = 0;
        $valorHora = $profesor->valor_hora ?? 0;

        foreach ($clasesDelProfesor as $clase) {
            if (!$clase->esLiquidable()) {
                continue;
            }

            $detalles[] = [
                'tipo' => 'clase',
                'referencia_id' => $clase->id,
                'descripcion' => sprintf(
                    'Clase %s - %s',
                    $clase->fecha->format('d/m/Y'),
                    $clase->grupo->nombre ?? 'Sin grupo'
                ),
                'monto' => $valorHora,
            ];

            $total += $valorHora;
        }

        return ['detalles' => $detalles, 'total' => $total];
    }

    /**
     * Previsualizar liquidación por comisión
     * Solo incluye alumnos que asistieron a clases de ESTE profesor
     */
    private function previsualizarLiquidacionComision(Profesor $profesor, int $mes, int $anio): array
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        $deporteId = $profesor->deporte_id;
        $porcentajeComision = $profesor->porcentaje_comision ?? 0;

        $alumnosConPago = Pago::where('mes', $mes)
            ->where('anio', $anio)
            ->where('estado', 'pagado')
            ->whereHas('alumno', function ($query) use ($deporteId) {
                $query->where('deporte_id', $deporteId)
                    ->where('activo', true);
            })
            ->with('alumno')
            ->get();

        $detalles = [];
        $total = 0;

        foreach ($alumnosConPago as $pago) {
            $alumno = $pago->alumno;

            // Verificar si el alumno asistió a al menos una clase de ESTE profesor
            $tieneAsistenciaConProfesor = Asistencia::where('alumno_id', $alumno->id)
                ->where('presente', true)
                ->whereHas('clase', function ($query) use ($fechaInicio, $fechaFin, $profesor) {
                    $query->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->where('cancelada', false)
                        ->whereHas('profesores', function ($q) use ($profesor) {
                            $q->where('profesores.id', $profesor->id);
                        });
                })
                ->exists();

            if (!$tieneAsistenciaConProfesor) {
                continue;
            }

            $montoComision = round($pago->monto_final * ($porcentajeComision / 100), 2);

            $detalles[] = [
                'tipo' => 'alumno',
                'referencia_id' => $alumno->id,
                'descripcion' => sprintf(
                    '%s %s - Pago $%s',
                    $alumno->nombre,
                    $alumno->apellido,
                    number_format($pago->monto_final, 2, ',', '.')
                ),
                'monto' => $montoComision,
            ];

            $total += $montoComision;
        }

        return ['detalles' => $detalles, 'total' => $total];
    }
}
