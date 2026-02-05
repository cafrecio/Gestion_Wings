<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\Pago;
use App\Models\ReglaPrimerPago;
use Carbon\Carbon;

class PagoService
{
    /**
     * Registrar un nuevo pago para un alumno
     *
     * CASO A: Primer pago → Aplica reglas editables desde BD
     * CASO B: Pagos siguientes → 100% del precio mensual
     *
     * Validaciones:
     * - No permite duplicar pago del mismo mes/año
     * - Requiere plan activo
     * - Para primer pago: aplica reglas automáticas o permite override manual
     *
     * @param int $alumnoId
     * @param int $mes (1-12)
     * @param int $anio
     * @param int $formaPagoId
     * @param string $fechaPago - Fecha de pago (fecha de negocio)
     * @param string|null $observaciones - Observaciones opcionales
     * @param float|null $porcentajeManual - Override manual del porcentaje (0-100)
     * @param int|null $reglaPrimerPagoId - ID de regla específica (opcional)
     * @return Pago
     * @throws \Exception
     */
    public function registrarPago(
        int $alumnoId,
        int $mes,
        int $anio,
        int $formaPagoId,
        string $fechaPago,
        ?string $observaciones = null,
        ?float $porcentajeManual = null,
        ?int $reglaPrimerPagoId = null
    ): Pago {
        $alumno = Alumno::findOrFail($alumnoId);

        // Validar que no exista pago duplicado para el mismo mes/año
        $this->validarPagoDuplicado($alumnoId, $mes, $anio);

        // 1. Obtener plan activo del alumno (plan vigente al momento del pago)
        $planActivo = $alumno->planActivo()->first();
        if (!$planActivo) {
            throw new \Exception('El alumno no tiene un plan activo asignado.');
        }

        // 2. Obtener el monto base del plan vigente
        $montoBase = $planActivo->plan->precio_mensual;

        // 3. Determinar si es primer pago
        $esPrimerPago = !$alumno->tienePagos();

        // 4. Calcular porcentaje y regla aplicada
        $porcentajeAplicado = 100.00;
        $reglaAplicadaId = null;

        if ($esPrimerPago) {
            [$porcentajeAplicado, $reglaAplicadaId] = $this->calcularPorcentajePrimerPago(
                $alumno,
                $porcentajeManual,
                $reglaPrimerPagoId
            );
        }

        // 5. Calcular monto final
        $montoFinal = round($montoBase * ($porcentajeAplicado / 100), 2);

        // 6. Crear el pago (INMUTABLE - no se modifica después de creado)
        $pago = Pago::create([
            'alumno_id' => $alumnoId,
            'plan_id' => $planActivo->plan_id,
            'regla_primer_pago_id' => $reglaAplicadaId,
            'mes' => $mes,
            'anio' => $anio,
            'monto_base' => $montoBase,
            'porcentaje_aplicado' => $porcentajeAplicado,
            'monto_final' => $montoFinal,
            'forma_pago_id' => $formaPagoId,
            'fecha_pago' => $fechaPago,
            'observaciones' => $observaciones,
            'estado' => 'pagado',
        ]);

        return $pago;
    }

    /**
     * Validar que no exista un pago duplicado para el mismo mes/año
     *
     * @param int $alumnoId
     * @param int $mes
     * @param int $anio
     * @throws \Exception
     */
    private function validarPagoDuplicado(int $alumnoId, int $mes, int $anio): void
    {
        $existe = Pago::where('alumno_id', $alumnoId)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->exists();

        if ($existe) {
            throw new \Exception("Ya existe un pago registrado para {$mes}/{$anio}.");
        }
    }

    /**
     * Calcular porcentaje a aplicar en el primer pago
     *
     * Prioridad:
     * 1. Porcentaje manual (si se provee)
     * 2. Regla específica seleccionada
     * 3. Regla automática según día de alta
     *
     * @param Alumno $alumno
     * @param float|null $porcentajeManual
     * @param int|null $reglaPrimerPagoId
     * @return array [porcentaje, reglaId]
     * @throws \Exception
     */
    private function calcularPorcentajePrimerPago(
        Alumno $alumno,
        ?float $porcentajeManual,
        ?int $reglaPrimerPagoId
    ): array {
        // Opción 1: Override manual
        if ($porcentajeManual !== null) {
            return [$porcentajeManual, $reglaPrimerPagoId];
        }

        // Opción 2: Regla específica seleccionada manualmente
        if ($reglaPrimerPagoId !== null) {
            $regla = ReglaPrimerPago::findOrFail($reglaPrimerPagoId);
            return [$regla->porcentaje, $regla->id];
        }

        // Opción 3: Calcular automáticamente según fecha de alta
        $diaAlta = $alumno->fecha_alta->day;
        $reglasAplicables = ReglaPrimerPago::obtenerReglaPorDia($diaAlta);

        if ($reglasAplicables->count() === 1) {
            $regla = $reglasAplicables->first();
            return [$regla->porcentaje, $regla->id];
        }

        if ($reglasAplicables->count() > 1) {
            throw new \Exception(
                'Hay múltiples reglas aplicables para el día ' . $diaAlta . '. ' .
                'Seleccione una regla manualmente o ingrese el porcentaje.'
            );
        }

        throw new \Exception(
            'No hay reglas aplicables para el día ' . $diaAlta . '. ' .
            'Ingrese el porcentaje manualmente o seleccione una regla.'
        );
    }

    /**
     * Cambiar el plan de un alumno
     * NO recalcula pagos anteriores (sin retroactividad)
     * 
     * @param int $alumnoId
     * @param int $nuevoPlanId
     * @return AlumnoPlan
     */
    public function cambiarPlan(int $alumnoId, int $nuevoPlanId): AlumnoPlan
    {
        $alumno = Alumno::findOrFail($alumnoId);

        // Crear nuevo plan (el modelo se encarga de desactivar el anterior)
        $nuevoPlan = AlumnoPlan::create([
            'alumno_id' => $alumnoId,
            'plan_id' => $nuevoPlanId,
            'fecha_desde' => Carbon::now()->toDateString(),
            'fecha_hasta' => null,
            'activo' => true,
        ]);

        return $nuevoPlan;
    }

    /**
     * Obtener reglas de primer pago disponibles para un día específico
     *
     * @param int $dia (1-31)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerReglasDisponibles(int $dia)
    {
        return ReglaPrimerPago::obtenerReglaPorDia($dia);
    }

    /**
     * Obtener información del próximo pago a realizar
     * Útil para mostrar en UI el siguiente mes a pagar
     *
     * @param int $alumnoId
     * @return array|null [mes, anio, monto_estimado, es_primer_pago]
     */
    public function obtenerProximoPago(int $alumnoId): ?array
    {
        $alumno = Alumno::findOrFail($alumnoId);

        $planActivo = $alumno->planActivo()->first();
        if (!$planActivo) {
            return null;
        }

        $esPrimerPago = !$alumno->tienePagos();

        if ($esPrimerPago) {
            // Para primer pago, usar mes/año actual o de alta
            $fechaReferencia = $alumno->fecha_alta->greaterThan(Carbon::now())
                ? $alumno->fecha_alta
                : Carbon::now();

            return [
                'mes' => $fechaReferencia->month,
                'anio' => $fechaReferencia->year,
                'monto_estimado' => null, // Depende de regla, no se puede calcular sin contexto
                'es_primer_pago' => true,
                'plan_nombre' => $planActivo->plan->grupo->nombre ?? 'Sin grupo',
                'clases_por_semana' => $planActivo->plan->clases_por_semana,
            ];
        }

        // Obtener último pago
        $ultimoPago = $alumno->pagos()->latest('anio')->latest('mes')->first();

        // Calcular siguiente mes
        $fecha = Carbon::createFromDate($ultimoPago->anio, $ultimoPago->mes, 1)->addMonth();

        return [
            'mes' => $fecha->month,
            'anio' => $fecha->year,
            'monto_estimado' => $planActivo->plan->precio_mensual,
            'es_primer_pago' => false,
            'plan_nombre' => $planActivo->plan->grupo->nombre ?? 'Sin grupo',
            'clases_por_semana' => $planActivo->plan->clases_por_semana,
        ];
    }

    /**
     * Verificar si un alumno puede registrar un pago
     *
     * @param int $alumnoId
     * @return array [puede_pagar: bool, razon: string|null]
     */
    public function verificarPuedePagar(int $alumnoId): array
    {
        try {
            $alumno = Alumno::findOrFail($alumnoId);

            if (!$alumno->activo) {
                return [
                    'puede_pagar' => false,
                    'razon' => 'El alumno está inactivo.',
                ];
            }

            $planActivo = $alumno->planActivo()->first();
            if (!$planActivo) {
                return [
                    'puede_pagar' => false,
                    'razon' => 'El alumno no tiene un plan activo asignado.',
                ];
            }

            return [
                'puede_pagar' => true,
                'razon' => null,
            ];
        } catch (\Exception $e) {
            return [
                'puede_pagar' => false,
                'razon' => $e->getMessage(),
            ];
        }
    }
}
