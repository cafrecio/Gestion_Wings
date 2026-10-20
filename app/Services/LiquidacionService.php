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
                'porcentaje_comision_aplicado' => ($tipoLiquidacion === Liquidacion::TIPO_COMISION)
                    ? $profesor->porcentaje_comision
                    : null,
                'valor_hora_aplicado' => ($tipoLiquidacion === Liquidacion::TIPO_HORA)
                    ? $profesor->valor_hora
                    : null,
                'total_calculado' => 0,
                'estado' => Liquidacion::ESTADO_ABIERTA,
            ]);

            if ($tipoLiquidacion === Deporte::TIPO_LIQUIDACION_HORA) {
                $total = $this->calcularLiquidacionHora($liquidacion, $profesor, $mes, $anio);
            } else {
                $total = $this->calcularLiquidacionComision($liquidacion, $profesor, $mes, $anio);
            }

            $liquidacion->update(['total_calculado' => $total]);

            // Vincular liquidaciones canceladas previas del mismo período y profesor
            Liquidacion::where('profesor_id', $profesor->id)
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->where('estado', Liquidacion::ESTADO_CANCELADA)
                ->whereNull('reemplazada_por_id')
                ->update(['reemplazada_por_id' => $liquidacion->id]);

            return $liquidacion->fresh(['detalles', 'profesor']);
        });
    }

    /**
     * Calcular liquidación por HORA
     * Se liquidan clases con asistencia o validadas manualmente.
     * Cada clase se paga tarifa x minutos / 60, con la tarifa congelada en la
     * liquidación y los minutos congelados en cada detalle.
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
        $valorHora = (float) ($liquidacion->valor_hora_aplicado ?? $profesor->valor_hora ?? 0);
        $total = 0;

        foreach ($this->clasesLiquidablesHora($profesor, $mes, $anio) as $clase) {
            $minutos = $this->minutosDeClase($clase);
            $monto = $this->montoPorClase($valorHora, $minutos);

            LiquidacionDetalle::create([
                'liquidacion_id' => $liquidacion->id,
                'tipo_referencia' => LiquidacionDetalle::TIPO_CLASE,
                'referencia_id' => $clase->id,
                'monto' => $monto,
                'minutos' => $minutos,
                'descripcion' => sprintf(
                    'Clase %s - %s (%s)',
                    $clase->fecha->format('d/m/Y'),
                    $clase->grupo->nombre ?? 'Sin grupo',
                    $clase->validada_para_liquidacion ? 'Validada manual' : 'Con asistencia'
                ),
            ]);

            $total += $monto;
        }

        return round($total, 2);
    }

    /**
     * Importe de una clase por hora. Único cálculo: lo usan la liquidación y la vista previa.
     */
    public function montoPorClase(float $valorHora, int $minutos): float
    {
        return round($valorHora * $minutos / 60, 2);
    }

    /**
     * Duración real de la clase en minutos. Una clase sin duración válida no se
     * liquida en silencio con un valor inventado: frena y dice cuál es.
     */
    private function minutosDeClase(Clase $clase): int
    {
        $minutos = ($clase->hora_inicio && $clase->hora_fin)
            ? (int) $clase->hora_inicio->diffInMinutes($clase->hora_fin, false)
            : 0;

        if ($minutos <= 0) {
            throw new \Exception(sprintf(
                'La clase del %s (%s) no tiene un horario válido: la hora de fin debe ser posterior a la de inicio.',
                $clase->fecha->format('d/m/Y'),
                $clase->grupo->nombre ?? 'Sin grupo'
            ));
        }

        return $minutos;
    }

    /**
     * Clases que entran en la liquidación por hora del mes.
     */
    private function clasesLiquidablesHora(Profesor $profesor, int $mes, int $anio)
    {
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = Carbon::createFromDate($anio, $mes, 1)->endOfMonth();

        return Clase::whereHas('profesores', function ($query) use ($profesor) {
            $query->where('profesores.id', $profesor->id);
        })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('cancelada', false)
            ->where(function ($q) {
                $q->where('validada_para_liquidacion', true)
                  ->orWhereHas('asistencias', fn ($a) => $a->where('presente', true));
            })
            ->with(['grupo.deporte', 'grupo.nivel'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
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
        int $anio,
        array $decisionesPrevias = []
    ): float {
        if ($liquidacion->estado !== Liquidacion::ESTADO_ABIERTA || $liquidacion->estaPagada()) {
            throw new \Exception('Solo se calcula una liquidación abierta sin pagar.');
        }
        $fechaInicio = Carbon::createFromDate($anio, $mes, 1)->startOfMonth();
        $fechaFin = $fechaInicio->copy()->endOfMonth();
        $porcentaje = $liquidacion->porcentaje_comision_aplicado ?? $profesor->porcentaje_comision ?? 0;
        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $servicio = app(PartesPagoCuotaService::class);
        $pagos = $servicio->pagosParaPeriodo($periodo)->whereHas('alumno')->get();
        $presentes = Asistencia::where('presente', true)
            ->whereHas('clase', fn ($q) => $q->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->where('cancelada', false)->whereHas('profesores', fn ($q) => $q->where('profesores.id', $profesor->id)))
            ->distinct()->pluck('alumno_id')->flip();
        $total = 0;
        $excluido = 0;
        foreach ($pagos as $pago) {
            if (!$presentes->has($pago->alumno_id)) continue;
            $partes = array_values(array_filter($servicio->partes($pago), fn ($p) => $p['periodo_comision'] === $periodo));
            foreach ($this->repartirComision($partes, (float) $porcentaje) as $parte) {
                $datos = [
                    'liquidacion_id' => $liquidacion->id, 'tipo_referencia' => LiquidacionDetalle::TIPO_ALUMNO,
                    'referencia_id' => $pago->alumno_id, 'pago_id' => $pago->id,
                    'deuda_cuota_id' => $parte['deuda_id'], 'periodo_cuota' => $parte['periodo_cuota'],
                    'fecha_cobro' => $pago->fecha_pago, 'origen_cuota' => $parte['origen'],
                    'base_cuota' => number_format($parte['centavos'] / 100, 2, '.', ''),
                    'monto' => number_format($parte['comision_centavos'] / 100, 2, '.', ''),
                    'descripcion' => sprintf('Comisión %s %s - Pago $%s (%s%%)%s',
                        $pago->alumno->nombre, $pago->alumno->apellido,
                        number_format($parte['centavos'] / 100, 2, ',', '.'), $porcentaje,
                        $parte['periodo_cuota'] ? ' - Cuota '.$parte['periodo_cuota'] : ''),
                ];
                $previa = $decisionesPrevias[$this->claveDecision($datos)] ?? [];
                $atrasada = $parte['origen'] === LiquidacionDetalle::ORIGEN_ATRASADO;
                $datos += [
                    'decision_atrasada' => $atrasada ? ($previa['decision_atrasada'] ?? LiquidacionDetalle::DECISION_PENDIENTE) : null,
                    'decision_por' => $previa['decision_por'] ?? null, 'decision_at' => $previa['decision_at'] ?? null,
                ];
                LiquidacionDetalle::create($datos);
                $total += $parte['comision_centavos'];
                if ($atrasada && $datos['decision_atrasada'] !== LiquidacionDetalle::DECISION_APROBADA) {
                    $excluido += $parte['comision_centavos'];
                }
            }
        }
        // Se guarda junto con total_calculado en la transacción de generar/recalcular.
        $liquidacion->total_atrasadas_excluidas = number_format($excluido / 100, 2, '.', '');
        return $total / 100;
    }

    /** Conserva el redondeo por pago/mes de T42 y reparte sus centavos sin crearlos. */
    private function repartirComision(array $partes, float $porcentaje): array
    {
        usort($partes, fn ($a, $b) => [$a['periodo_cuota'] ?? '', $a['deuda_id'] ?? 0]
            <=> [$b['periodo_cuota'] ?? '', $b['deuda_id'] ?? 0]);
        $tasa = (int) round($porcentaje * 100);
        $objetivo = intdiv(array_sum(array_column($partes, 'centavos')) * $tasa + 5000, 10000);
        $restos = [];
        $asignado = 0;
        foreach ($partes as $i => &$parte) {
            $producto = $parte['centavos'] * $tasa;
            $parte['comision_centavos'] = intdiv($producto, 10000);
            $restos[$i] = $producto % 10000;
            $asignado += $parte['comision_centavos'];
        }
        unset($parte);
        arsort($restos);
        foreach (array_keys($restos) as $i) {
            if ($asignado >= $objetivo) break;
            $partes[$i]['comision_centavos']++;
            $asignado++;
        }
        return $partes;
    }

    private function claveDecision(array $datos): string
    {
        return implode(':', [(int) ($datos['pago_id'] ?? 0), (int) ($datos['deuda_cuota_id'] ?? 0),
            $datos['periodo_cuota'] ?? '', (int) round((float) ($datos['base_cuota'] ?? 0) * 100),
            (int) round((float) ($datos['monto'] ?? 0) * 100)]);
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
        return DB::transaction(function () use ($liquidacionId) {
            $liquidacion = Liquidacion::lockForUpdate()->findOrFail($liquidacionId);
            if ($liquidacion->estaCerrada()) throw new \Exception('La liquidación ya está cerrada.');
            if ($liquidacion->tieneAtrasadasPendientes()) {
                throw new \Exception('Primero validá o rechazá cada cuota atrasada.');
            }
            if (!$liquidacion->puedeCerrarse()) {
                $desde = $liquidacion->fechaHabilitacionCierre()->format('d/m/Y');
                throw new \Exception("No se puede cerrar la liquidación antes de que termine el mes. Se puede cerrar desde el {$desde}.");
            }
            $liquidacion->update(['estado' => Liquidacion::ESTADO_CERRADA]);
            return $liquidacion->fresh();
        });
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
        return DB::transaction(function () use ($liquidacionId) {
            $liquidacion = Liquidacion::with('profesor.deporte')->lockForUpdate()->findOrFail($liquidacionId);
            if ($liquidacion->estado !== Liquidacion::ESTADO_ABIERTA || $liquidacion->estaPagada()) {
                throw new \Exception('No se puede recalcular una liquidación cerrada, cancelada o pagada.');
            }
            $previas = [];
            foreach ($liquidacion->detalles()->get() as $detalle) {
                if ($detalle->origen_cuota === LiquidacionDetalle::ORIGEN_ATRASADO
                    && in_array($detalle->decision_atrasada, [LiquidacionDetalle::DECISION_APROBADA, LiquidacionDetalle::DECISION_RECHAZADA], true)) {
                    $previas[$this->claveDecision($detalle->getAttributes())] = $detalle->only(['decision_atrasada', 'decision_por', 'decision_at']);
                }
            }
            $liquidacion->detalles()->delete();
            $profesor = $liquidacion->profesor;
            $total = $liquidacion->tipo === Liquidacion::TIPO_HORA
                ? $this->calcularLiquidacionHora($liquidacion, $profesor, $liquidacion->mes, $liquidacion->anio)
                : $this->calcularLiquidacionComision($liquidacion, $profesor, $liquidacion->mes, $liquidacion->anio, $previas);
            $liquidacion->update(['total_calculado' => $total]);
            return $liquidacion->fresh(['detalles', 'profesor']);
        });
    }

    public function decidirAtrasada(int $liquidacionId, int $detalleId, string $decision, int $adminId, string $esperada): Liquidacion
    {
        $admin = \App\Models\User::findOrFail($adminId);
        if (!$admin->isAdmin() || !$admin->isActivo()) throw new \Illuminate\Auth\Access\AuthorizationException();
        if (!in_array($decision, [LiquidacionDetalle::DECISION_APROBADA, LiquidacionDetalle::DECISION_RECHAZADA], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['decision' => 'Elegí validar o rechazar.']);
        }
        return DB::transaction(function () use ($liquidacionId, $detalleId, $decision, $adminId, $esperada) {
            $liq = Liquidacion::lockForUpdate()->findOrFail($liquidacionId);
            $detalle = $liq->detalles()->lockForUpdate()->findOrFail($detalleId);
            if ($liq->tipo !== Liquidacion::TIPO_COMISION || $liq->estado !== Liquidacion::ESTADO_ABIERTA || $liq->estaPagada()
                || $detalle->origen_cuota !== LiquidacionDetalle::ORIGEN_ATRASADO) {
                throw \Illuminate\Validation\ValidationException::withMessages(['decision' => 'Solo se revisan cuotas atrasadas de una liquidación abierta sin pagar.']);
            }
            $anterior = $detalle->decision_atrasada ?? LiquidacionDetalle::DECISION_PENDIENTE;
            if ($anterior === $decision) return $liq->fresh(['detalles', 'profesor']);
            if ($anterior !== $esperada) {
                throw \Illuminate\Validation\ValidationException::withMessages(['decision' => 'La decisión cambió. Recargá la liquidación antes de revisar.']);
            }
            $detalle->update(['decision_atrasada' => $decision, 'decision_por' => $adminId, 'decision_at' => now()]);
            DB::table('liquidacion_decisiones_atrasadas')->insert([
                'liquidacion_id' => $liq->id, 'detalle_id' => $detalle->id, 'profesor_id' => $liq->profesor_id,
                'periodo_liquidacion' => sprintf('%04d-%02d', $liq->anio, $liq->mes),
                'pago_id' => $detalle->pago_id, 'deuda_cuota_id' => $detalle->deuda_cuota_id,
                'periodo_cuota' => $detalle->periodo_cuota, 'base_cuota' => $detalle->base_cuota,
                'comision' => $detalle->monto, 'anterior' => $anterior, 'nueva' => $decision,
                'admin_id' => $adminId, 'registrado_en' => now(),
            ]);
            $excluido = $liq->detalles()->where('origen_cuota', LiquidacionDetalle::ORIGEN_ATRASADO)
                ->where(fn ($q) => $q->whereNull('decision_atrasada')->orWhere('decision_atrasada', '!=', LiquidacionDetalle::DECISION_APROBADA))
                ->sum('monto');
            $liq->update(['total_atrasadas_excluidas' => $excluido]);
            return $liq->fresh(['detalles', 'profesor']);
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
            'total_monto' => $liquidaciones->where('estado', '!=', Liquidacion::ESTADO_CANCELADA)->sum('monto_a_pagar'),
            'abiertas' => $liquidaciones->where('estado', Liquidacion::ESTADO_ABIERTA)->count(),
            'cerradas' => $liquidaciones->where('estado', Liquidacion::ESTADO_CERRADA)->count(),
            'canceladas' => $liquidaciones->where('estado', Liquidacion::ESTADO_CANCELADA)->count(),
            'por_tipo' => [
                'HORA' => $liquidaciones->where('estado', '!=', Liquidacion::ESTADO_CANCELADA)->where('tipo', Liquidacion::TIPO_HORA)->sum('monto_a_pagar'),
                'COMISION' => $liquidaciones->where('estado', '!=', Liquidacion::ESTADO_CANCELADA)->where('tipo', Liquidacion::TIPO_COMISION)->sum('monto_a_pagar'),
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
            ->where('estado', '!=', Liquidacion::ESTADO_CANCELADA)
            ->exists();

        if ($existe) {
            throw new \Exception(
                sprintf('Ya existe una liquidación para el profesor en %02d/%d.', $mes, $anio)
            );
        }
    }

    /**
     * Cancelar una liquidación cerrada que aún no fue pagada (FIN-12).
     * Permite volver a revisar asistencias y regenerar la liquidación.
     * La liquidación cancelada y su detalle se conservan para auditoría.
     *
     * @param int $liquidacionId
     * @param string $motivo
     * @param int $adminId
     * @return Liquidacion
     * @throws \Exception
     */
    public function cancelarLiquidacion(int $liquidacionId, string $motivo, int $adminId): Liquidacion
    {
        $motivoLimpio = trim($motivo);
        if ($motivoLimpio === '') {
            throw new \Exception('El motivo de cancelación es obligatorio.');
        }

        return DB::transaction(function () use ($liquidacionId, $motivoLimpio, $adminId) {
            $liquidacion = Liquidacion::lockForUpdate()->findOrFail($liquidacionId);

            // Idempotencia: si ya está cancelada, retornar sin error
            if ($liquidacion->estaCancelada()) {
                return $liquidacion;
            }

            if ($liquidacion->estaPagada()) {
                throw new \Exception('No se puede cancelar una liquidación pagada.');
            }

            if (!$liquidacion->estaCerrada()) {
                throw new \Exception('Solo se pueden cancelar liquidaciones cerradas.');
            }

            $liquidacion->estado = Liquidacion::ESTADO_CANCELADA;
            $liquidacion->usuario_cancelacion_id = $adminId;
            $liquidacion->cancelada_at = Carbon::now();
            $liquidacion->motivo_cancelacion = $motivoLimpio;
            $liquidacion->save();

            return $liquidacion->fresh(['detalles', 'profesor', 'usuarioCancelacion', 'reemplazadaPor']);
        });
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

        if ($liquidacion->estaCerrada() || $liquidacion->estaCancelada()) {
            throw new \Exception('No se puede eliminar una liquidación cerrada o cancelada.');
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
        $detalles = [];
        $total = 0;
        $valorHora = (float) ($profesor->valor_hora ?? 0);

        foreach ($this->clasesLiquidablesHora($profesor, $mes, $anio) as $clase) {
            $minutos = $this->minutosDeClase($clase);
            $monto = $this->montoPorClase($valorHora, $minutos);

            $detalles[] = [
                'tipo' => 'clase',
                'referencia_id' => $clase->id,
                'descripcion' => sprintf(
                    'Clase %s - %s',
                    $clase->fecha->format('d/m/Y'),
                    $clase->grupo->nombre ?? 'Sin grupo'
                ),
                'minutos' => $minutos,
                'monto' => $monto,
            ];

            $total += $monto;
        }

        return ['detalles' => $detalles, 'total' => round($total, 2)];
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

        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $partes = app(PartesPagoCuotaService::class);
        $alumnosConPago = $partes->pagosParaPeriodo($periodo)
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
            $montoCuota = $partes->centavosParaPeriodo($pago, $periodo) / 100;
            if ($montoCuota <= 0) continue;

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

            $montoComision = round($montoCuota * ($porcentajeComision / 100), 2);

            $detalles[] = [
                'tipo' => 'alumno',
                'referencia_id' => $alumno->id,
                'descripcion' => sprintf(
                    '%s %s - Pago $%s',
                    $alumno->nombre,
                    $alumno->apellido,
                    number_format($montoCuota, 2, ',', '.')
                ),
                'monto' => $montoComision,
            ];

            $total += $montoComision;
        }

        return ['detalles' => $detalles, 'total' => $total];
    }
}
