<?php

namespace App\Services;

use App\Models\Alumno;
use App\Models\AlumnoPlan;
use App\Models\AlumnoRevisionCobranza;
use App\Models\CashflowMovimiento;
use App\Models\DeudaCuota;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\PagoDeudaCuota;
use App\Models\ReglaPrimerPago;
use App\Models\Subrubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoCuotaService
{
    private CajaService $cajaService;
    private ?ReciboService $reciboService;

    public function __construct(CajaService $cajaService, ?ReciboService $reciboService = null)
    {
        $this->cajaService = $cajaService;
        $this->reciboService = $reciboService;
    }

    /**
     * Registrar pago de cuota como OPERATIVO.
     * Genera MovimientoOperativo y abre caja automáticamente si no existe.
     *
     * @param array $data [alumno_id, tipo_caja_id, usuario_operativo_id, items[], fecha_pago?, observaciones?]
     * @return array ['pago' => Pago, 'movimiento' => MovimientoOperativo, 'deudas_actualizadas' => DeudaCuota[]]
     * @throws \Exception
     */
    public function registrarPagoCuotaOperativo(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $dni = Alumno::findOrFail($data['alumno_id'])->dni;
            app(InscripcionService::class)->bloquear($dni, false);
            $alumnoAntes = $this->bloquearAlumnoParaPago($data['alumno_id']);
            $this->exigirActivoParaCobrarElMes($alumnoAntes, $data);
            // T38: si este cobro reactiva al alumno, la cuota del mes queda como la de un reingreso.
            $reingreso = $alumnoAntes->activo ? null : [
                'cuota_del_mes_existia' => DeudaCuota::where('alumno_id', $data['alumno_id'])
                    ->where('periodo', now()->format('Y-m'))->exists(),
            ];

            // Tomar deuda y caja antes de escribir. Comparte las filas con
            // condonar/cancelar y validar; esperar no debe dejar lecturas viejas.
            DeudaCuota::where('alumno_id', $data['alumno_id'])
                ->whereIn('periodo', array_column($data['items'], 'periodo'))
                ->orderBy('periodo')->lockForUpdate()->get();
            $this->cajaService->abrirCajaSiNoExiste($data['usuario_operativo_id']);

            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $items = $this->ordenarItemsPorPeriodo($data['items']);
            // T18: un mes cobrado por adelantado nace valiendo lo que se cobró.
            $montosOriginalesNuevasDeudas = $data['montos_adelantados'] ?? [];

            // Regla de primer pago: ajustar montos si aplica
            [$porcentaje, $reglaId, $periodoConDescuento] = $this->calcularReglaPrimerPago($data['alumno_id'], $items);
            if ($porcentaje < 100 && $periodoConDescuento !== null) {
                // El descuento baja el PRECIO DEL MES, no el importe que se entrega.
                // Aplicarlo sobre lo tipeado convertia una seña de 10.000 en un cobro de
                // 7.000 que ademas cerraba el mes entero.
                $precioConDescuento = $this->precioConDescuento($data['alumno_id'], $periodoConDescuento, $porcentaje);
                $montosOriginalesNuevasDeudas[$periodoConDescuento] = $precioConDescuento;
                $this->ajustarDeudaConDescuento($data['alumno_id'], $periodoConDescuento, $precioConDescuento, $fechaPago);
                $items = $this->limitarAlSaldoConDescuento($items, $data['alumno_id'], $periodoConDescuento, $precioConDescuento);
            }

            [$items, $cargoInscripcion, $montoInscripcion, $punitorios] = $this->distribuirInscripcion($data, $items);
            $montoCuota = $this->calcularMontoTotal($items);
            $montoTotal = round($montoCuota + $montoInscripcion + array_sum(array_column($punitorios, 'monto')), 2);

            // Validar FIFO antes de aplicar pagos
            $this->validarFifo($data['alumno_id'], $items, $montosOriginalesNuevasDeudas);

            $excepciones = app(ExcepcionCobranzaService::class)->validarCobro(
                $data['alumno_id'], $items, $montosOriginalesNuevasDeudas, $punitorios, $data['motivo'] ?? null
            );

            // Validar y procesar deudas (auto-crea DeudaCuota si no existe)
            [$deudasActualizadas, $montosAplicados] = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $items,
                $montosOriginalesNuevasDeudas,
                $fechaPago
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null,
                $porcentaje,
                $reglaId,
                $montoCuota
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $items, $deudasActualizadas, $montosAplicados);

            app(ExcepcionCobranzaService::class)->guardar($excepciones, $data['motivo'] ?? '',
                $data['usuario_operativo_id'], $data['alumno_id'], 'PAGO', $pago->id);

            if ($montoInscripcion > 0) {
                $pago->cargos()->attach($cargoInscripcion->id, ['monto_aplicado' => $montoInscripcion]);
            }
            foreach ($punitorios as $imputacion) {
                $pago->cargos()->attach($imputacion['cargo']->id, ['monto_aplicado' => $imputacion['monto']]);
            }

            // Si el alumno estaba inactivo o en revisión, reactivar al pagar solo si tiene plan vigente para el mes
            // y quien cobra no contestó que sigue inactivo (T25).
            $planPeriodoActual = $this->obtenerPlanParaPeriodo($data['alumno_id'], now()->format('Y-m'));
            if ($montoCuota > 0 && $planPeriodoActual?->plan && ($data['reactivar'] ?? true)) {
                if (!$alumnoAntes->activo) $alumnoAntes->update(['activo' => true]);
                if ($reingreso !== null) {
                    $this->completarReingresoPorCobro($alumnoAntes, $reingreso['cuota_del_mes_existia'], $porcentaje, $periodoConDescuento);
                }
            }
            foreach ($items as $item) {
                AlumnoRevisionCobranza::reactivarAuto($data['alumno_id'], $item['periodo']);
            }

            // Crear movimiento operativo (abre caja si no existe)
            // Usa método interno para permitir subrubro reservado "Cuota Mensual"
            $movimiento = $montoCuota > 0 ? $this->cajaService->registrarMovimientoOperativoInterno([
                'usuario_operativo_id' => $data['usuario_operativo_id'],
                'tipo_caja_id'         => $data['tipo_caja_id'],
                'subrubro_id'          => $subruboCuota->id,
                'monto'                => $montoCuota,
                'fecha'                => $fechaPago,
                'observaciones'        => $this->generarObservacionesPago($data['alumno_id'], $items, $data['observaciones'] ?? null),
                'alumno_id'            => $data['alumno_id'],
                'pago_id'              => $pago->id,
            ]) : null;
            if ($montoInscripcion > 0) {
                $movCargo = $this->cajaService->registrarMovimientoOperativoInterno([
                    'usuario_operativo_id' => $data['usuario_operativo_id'], 'tipo_caja_id' => $data['tipo_caja_id'],
                    'subrubro_id' => $cargoInscripcion->subrubro_id, 'monto' => $montoInscripcion,
                    'fecha' => $fechaPago, 'observaciones' => 'Inscripción al club - pago #'.$pago->id,
                    'alumno_id' => $data['alumno_id'], 'pago_id' => $pago->id,
                ]);
                $movimiento ??= $movCargo;
            }
            foreach ($punitorios as $imputacion) {
                $movCargo = $this->cajaService->registrarMovimientoOperativoInterno([
                    'usuario_operativo_id' => $data['usuario_operativo_id'], 'tipo_caja_id' => $data['tipo_caja_id'],
                    'subrubro_id' => $imputacion['cargo']->subrubro_id, 'monto' => $imputacion['monto'],
                    'fecha' => $fechaPago, 'observaciones' => $imputacion['cargo']->descripcion.' - pago #'.$pago->id,
                    'alumno_id' => $data['alumno_id'], 'pago_id' => $pago->id,
                ]);
                $movimiento ??= $movCargo;
            }

            $resultado = [
                'pago' => $pago->load('deudasCuota'),
                'movimiento' => $movimiento,
                'deudas_actualizadas' => $deudasActualizadas,
            ];

            // Enganchar generación de PDF después del commit (no falla la transacción si el PDF falla)
            $pagoId = $pago->id;
            $reciboService = $this->reciboService;
            DB::afterCommit(function () use ($pagoId, $reciboService) {
                if ($reciboService) {
                    $reciboService->intentarGenerarReciboCuota($pagoId);
                }
            });

            return $resultado;
        });
    }

    /**
     * Registrar pago de cuota como ADMIN.
     * Genera CashflowMovimiento directo, sin caja operativa.
     *
     * @param array $data [alumno_id, tipo_caja_id, usuario_admin_id, items[], fecha_pago?, observaciones?]
     * @return array ['pago' => Pago, 'movimiento' => CashflowMovimiento, 'deudas_actualizadas' => DeudaCuota[]]
     * @throws \Exception
     */
    public function registrarPagoCuotaAdmin(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $dni = Alumno::findOrFail($data['alumno_id'])->dni;
            app(InscripcionService::class)->bloquear($dni, false);
            $alumnoAntes = $this->bloquearAlumnoParaPago($data['alumno_id']);
            $this->exigirActivoParaCobrarElMes($alumnoAntes, $data);
            // T38: si este cobro reactiva al alumno, la cuota del mes queda como la de un reingreso.
            $reingreso = $alumnoAntes->activo ? null : [
                'cuota_del_mes_existia' => DeudaCuota::where('alumno_id', $data['alumno_id'])
                    ->where('periodo', now()->format('Y-m'))->exists(),
            ];

            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $items = $this->ordenarItemsPorPeriodo($data['items']);
            // T18: un mes cobrado por adelantado nace valiendo lo que se cobró.
            $montosOriginalesNuevasDeudas = $data['montos_adelantados'] ?? [];

            // Regla de primer pago: ajustar montos si aplica
            [$porcentaje, $reglaId, $periodoConDescuento] = $this->calcularReglaPrimerPago($data['alumno_id'], $items);
            if ($porcentaje < 100 && $periodoConDescuento !== null) {
                // El descuento baja el PRECIO DEL MES, no el importe que se entrega.
                // Aplicarlo sobre lo tipeado convertia una seña de 10.000 en un cobro de
                // 7.000 que ademas cerraba el mes entero.
                $precioConDescuento = $this->precioConDescuento($data['alumno_id'], $periodoConDescuento, $porcentaje);
                $montosOriginalesNuevasDeudas[$periodoConDescuento] = $precioConDescuento;
                $this->ajustarDeudaConDescuento($data['alumno_id'], $periodoConDescuento, $precioConDescuento, $fechaPago);
                $items = $this->limitarAlSaldoConDescuento($items, $data['alumno_id'], $periodoConDescuento, $precioConDescuento);
            }

            [$items, $cargoInscripcion, $montoInscripcion, $punitorios] = $this->distribuirInscripcion($data, $items);
            $montoCuota = $this->calcularMontoTotal($items);
            $montoTotal = round($montoCuota + $montoInscripcion + array_sum(array_column($punitorios, 'monto')), 2);

            // Validar FIFO antes de aplicar pagos
            $this->validarFifo($data['alumno_id'], $items, $montosOriginalesNuevasDeudas);

            $excepciones = app(ExcepcionCobranzaService::class)->validarCobro(
                $data['alumno_id'], $items, $montosOriginalesNuevasDeudas, $punitorios, $data['motivo'] ?? null
            );

            // Validar y procesar deudas (auto-crea DeudaCuota si no existe)
            [$deudasActualizadas, $montosAplicados] = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $items,
                $montosOriginalesNuevasDeudas,
                $fechaPago
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null,
                $porcentaje,
                $reglaId,
                $montoCuota
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $items, $deudasActualizadas, $montosAplicados);

            app(ExcepcionCobranzaService::class)->guardar($excepciones, $data['motivo'] ?? '',
                $data['usuario_admin_id'], $data['alumno_id'], 'PAGO', $pago->id);

            if ($montoInscripcion > 0) {
                $pago->cargos()->attach($cargoInscripcion->id, ['monto_aplicado' => $montoInscripcion]);
            }
            foreach ($punitorios as $imputacion) {
                $pago->cargos()->attach($imputacion['cargo']->id, ['monto_aplicado' => $imputacion['monto']]);
            }

            // Si el alumno estaba inactivo o en revisión, reactivar al pagar solo si tiene plan vigente para el mes
            // y quien cobra no contestó que sigue inactivo (T25).
            $planPeriodoActual = $this->obtenerPlanParaPeriodo($data['alumno_id'], now()->format('Y-m'));
            if ($montoCuota > 0 && $planPeriodoActual?->plan && ($data['reactivar'] ?? true)) {
                if (!$alumnoAntes->activo) $alumnoAntes->update(['activo' => true]);
                if ($reingreso !== null) {
                    $this->completarReingresoPorCobro($alumnoAntes, $reingreso['cuota_del_mes_existia'], $porcentaje, $periodoConDescuento);
                }
            }
            foreach ($items as $item) {
                AlumnoRevisionCobranza::reactivarAuto($data['alumno_id'], $item['periodo']);
            }

            if ($montoCuota > 0) \App\Support\ClasificacionSubrubros::exigirSinTraspaso($subruboCuota);
            // Crear movimiento en cashflow directo
            $movimiento = $montoCuota > 0 ? CashflowMovimiento::create([
                'fecha' => $fechaPago,
                'subrubro_id' => $subruboCuota->id,
                'tipo_caja_id' => $data['tipo_caja_id'],
                'monto' => $montoCuota,
                'observaciones' => $this->generarObservacionesPago($data['alumno_id'], $items, $data['observaciones'] ?? null),
                'usuario_admin_id' => $data['usuario_admin_id'],
                'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA,
                'referencia_id' => $pago->id,
            ]) : null;
            if ($montoInscripcion > 0) {
                \App\Support\ClasificacionSubrubros::exigirSinTraspaso(Subrubro::findOrFail($cargoInscripcion->subrubro_id));
                $movCargo = CashflowMovimiento::create([
                    'fecha' => $fechaPago, 'subrubro_id' => $cargoInscripcion->subrubro_id,
                    'tipo_caja_id' => $data['tipo_caja_id'], 'monto' => $montoInscripcion,
                    'observaciones' => 'Inscripción al club - pago #'.$pago->id,
                    'usuario_admin_id' => $data['usuario_admin_id'],
                    'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA, 'referencia_id' => $pago->id,
                ]);
                $movimiento ??= $movCargo;
            }
            foreach ($punitorios as $imputacion) {
                \App\Support\ClasificacionSubrubros::exigirSinTraspaso(Subrubro::findOrFail($imputacion['cargo']->subrubro_id));
                $movCargo = CashflowMovimiento::create([
                    'fecha' => $fechaPago, 'subrubro_id' => $imputacion['cargo']->subrubro_id,
                    'tipo_caja_id' => $data['tipo_caja_id'], 'monto' => $imputacion['monto'],
                    'observaciones' => $imputacion['cargo']->descripcion.' - pago #'.$pago->id,
                    'usuario_admin_id' => $data['usuario_admin_id'],
                    'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA, 'referencia_id' => $pago->id,
                ]);
                $movimiento ??= $movCargo;
            }

            $resultado = [
                'pago' => $pago->load('deudasCuota'),
                'movimiento' => $movimiento,
                'deudas_actualizadas' => $deudasActualizadas,
            ];

            // Enganchar generación de PDF después del commit (no falla la transacción si el PDF falla)
            $pagoId = $pago->id;
            $reciboService = $this->reciboService;
            DB::afterCommit(function () use ($pagoId, $reciboService) {
                if ($reciboService) {
                    $reciboService->intentarGenerarReciboCuota($pagoId);
                }
            });

            return $resultado;
        });
    }

    /**
     * Condonar una deuda (solo ADMIN).
     * No genera movimiento de caja. Estado pasa a CONDONADA.
     *
     * @param int $deudaId
     * @param string $observaciones
     * @param int $adminId
     * @return DeudaCuota
     * @throws \Exception
     */
    public function condonarDeuda(int $deudaId, string $observaciones, int $adminId): DeudaCuota
    {
        $observaciones = trim($observaciones);
        $longitudMotivo = mb_strlen($observaciones);

        if ($longitudMotivo < 10 || $longitudMotivo > 500) {
            throw new \InvalidArgumentException('El motivo de la condonación debe tener entre 10 y 500 caracteres.');
        }

        return DB::transaction(function () use ($deudaId, $observaciones, $adminId) {
            $deuda = $this->bloquearDeudaParaDecision($deudaId);

            if ($deuda->estado !== DeudaCuota::ESTADO_PENDIENTE) {
                throw new \Exception("Solo se pueden condonar deudas con estado PENDIENTE. Estado actual: {$deuda->estado}");
            }

            $saldoCondonado = number_format($deuda->saldo_pendiente, 2, '.', '');
            $deuda->estado = DeudaCuota::ESTADO_CONDONADA;
            // Se perdona solo el saldo; original, pagos e imputaciones son historia.
            $deuda->observaciones = $this->agregarObservacion(
                $deuda->observaciones,
                "CONDONADA por admin ID:{$adminId} - Saldo condonado: {$saldoCondonado} - {$observaciones}"
            );
            $deuda->save();

            return $deuda;
        });
    }

    public function reactivarDeuda(int $deudaId, string $motivo, int $adminId): DeudaCuota
    {
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10 || mb_strlen($motivo) > 500) {
            throw new \InvalidArgumentException('El motivo de la reactivación debe tener entre 10 y 500 caracteres.');
        }

        return DB::transaction(function () use ($deudaId, $motivo, $adminId) {
            $deuda = $this->bloquearDeudaParaDecision($deudaId);
            if ($deuda->estado !== DeudaCuota::ESTADO_CONDONADA) {
                throw new \RuntimeException('Solo se pueden reactivar cuotas CONDONADAS.');
            }
            $deuda->estado = DeudaCuota::ESTADO_PENDIENTE;
            $deuda->observaciones = $this->agregarObservacion($deuda->observaciones,
                "REACTIVADA por admin ID:{$adminId} - Saldo reactivado: "
                . number_format($deuda->saldo_pendiente, 2, '.', '') . " - {$motivo}");
            $deuda->save();
            return $deuda;
        });
    }

    private function bloquearDeudaParaDecision(int $deudaId): DeudaCuota
    {
        // La primera lectura solo localiza la persona; estado y saldo se releen bajo bloqueo.
        $alumno = Alumno::findOrFail(DeudaCuota::findOrFail($deudaId)->alumno_id);
        app(InscripcionService::class)->bloquear($alumno->dni, false);
        $this->bloquearAlumnoParaPago($alumno->id);
        return DeudaCuota::whereKey($deudaId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Ajustar una deuda (solo ADMIN).
     * Permite modificar monto_original dejando trazabilidad.
     *
     * @param int $deudaId
     * @param float $nuevoMonto
     * @param string $observaciones
     * @param int $adminId
     * @return DeudaCuota
     * @throws \Exception
     */
    public function ajustarDeuda(int $deudaId, float $nuevoMonto, string $observaciones, int $adminId): DeudaCuota
    {
        $deuda = DeudaCuota::findOrFail($deudaId);

        if (!in_array($deuda->estado, [DeudaCuota::ESTADO_PENDIENTE, DeudaCuota::ESTADO_AJUSTADA])) {
            throw new \Exception("Solo se pueden ajustar deudas PENDIENTES o ya AJUSTADAS. Estado actual: {$deuda->estado}");
        }

        $montoAnterior = $deuda->monto_original;
        $deuda->monto_original = $nuevoMonto;
        $deuda->estado = DeudaCuota::ESTADO_AJUSTADA;
        $deuda->observaciones = $this->agregarObservacion(
            $deuda->observaciones,
            "AJUSTADA por admin ID:{$adminId} - Monto anterior: {$montoAnterior}, nuevo: {$nuevoMonto} - {$observaciones}"
        );

        // Si el nuevo monto <= monto_pagado, marcar como pagada
        if ($deuda->monto_pagado >= $nuevoMonto) {
            $deuda->estado = DeudaCuota::ESTADO_PAGADA;
        }

        $deuda->save();

        return $deuda;
    }

    /**
     * Crear deuda si no existe para un alumno/período.
     *
     * @param int $alumnoId
     * @param string $periodo Formato YYYY-MM
     * @param float $montoOriginal
     * @return DeudaCuota
     */
    public function crearDeudaSiNoExiste(int $alumnoId, string $periodo, float $montoOriginal): DeudaCuota
    {
        return DeudaCuota::firstOrCreate(
            [
                'alumno_id' => $alumnoId,
                'periodo' => $periodo,
            ],
            [
                'alumno_id' => $alumnoId,
                'periodo' => $periodo,
                'monto_original' => $montoOriginal,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]
        );
    }

    /** Misma regla para el aviso previo y la deuda congelada al guardar. */
    public function previsualizarCuotaAlta(string $fechaIngreso, ?int $planId = null): array
    {
        if (!\App\Support\FechaIngresoRegla::esValida($fechaIngreso)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fecha_alta' => \App\Support\FechaIngresoRegla::mensajeError(),
            ]);
        }
        $fecha = Carbon::parse($fechaIngreso);
        $mesCerrado = $fecha->copy()->startOfMonth()->lt(now()->startOfMonth());
        $mesCuota = $mesCerrado ? now() : $fecha->copy();
        $periodo = $mesCuota->format('Y-m');
        [$porcentaje] = $mesCerrado ? [100.0] : $this->reglaDelDia($fecha->day, $periodo);
        $importe = null;
        if ($planId !== null) {
            $query = \App\Models\GrupoPlan::query();
            if (DB::transactionLevel() > 0) $query->lockForUpdate();
            $plan = $query->findOrFail($planId);
            $importe = $this->aplicarPorcentaje($plan->precioParaPeriodo($periodo, $fecha), $porcentaje);
        }

        return ['mes_cerrado' => $mesCerrado, 'periodo' => $periodo,
            'mes_ingreso' => $fecha->locale('es')->translatedFormat('F \d\e Y'),
            'mes_cuota' => $mesCuota->locale('es')->translatedFormat('F \d\e Y'),
            'porcentaje' => $porcentaje, 'importe' => $importe];
    }

    /** Se invoca dentro de la transacción de alta; nunca para alumnos existentes. */
    public function crearCuotaAlta(Alumno $alumno, int $planId, ?bool $generarCuotaActual = null): ?DeudaCuota
    {
        $vista = $this->previsualizarCuotaAlta($alumno->fecha_alta->format('Y-m-d'), $planId);
        if ($vista['mes_cerrado']) {
            if ($generarCuotaActual === null) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'generar_cuota_actual' => 'Elegí si se genera la cuota de este mes antes de guardar.',
                ]);
            }
            if (!$generarCuotaActual) return null;
        }

        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $vista['periodo'],
            'monto_original' => $vista['importe'],
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'porcentaje_alta' => $vista['porcentaje'],
        ]);
    }

    /**
     * T38 (DU-12, Carlos 13/10): al reactivar a un alumno se le genera la cuota del mes en
     * curso con el porcentaje de reingreso del día, salvo que ya la tenga. Lo que debía
     * antes queda como estaba. La cuota guarda el día del reingreso, que le da el mismo
     * plazo que una cuota de alta (Carlos, 17/10).
     *
     * Se invoca dentro de la transacción que reactiva, con el alumno bloqueado. Devuelve
     * null si la cuota del mes ya existía.
     */
    public function crearCuotaReingreso(Alumno $alumno): ?DeudaCuota
    {
        $hoy = now();
        $periodo = $hoy->format('Y-m');

        // Todavía no empezó (ingreso de un mes que viene): su primera cuota es la del alta.
        if ($alumno->fecha_alta && $alumno->fecha_alta->gt($hoy->copy()->endOfMonth())) {
            return null;
        }

        if (DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', $periodo)->lockForUpdate()->exists()) {
            return null;
        }

        $alumnoPlan = $this->obtenerPlanParaPeriodo($alumno->id, $periodo);
        if (!$alumnoPlan?->plan) {
            throw new \Exception('El alumno no tiene un plan para este mes. Asigná un plan antes de reactivarlo.');
        }

        [$porcentaje] = $this->reglaDelDia($hoy->day, $periodo);

        return DeudaCuota::create([
            'alumno_id' => $alumno->id,
            'periodo' => $periodo,
            'monto_original' => $this->aplicarPorcentaje($alumnoPlan->plan->precioParaPeriodo($periodo, $alumno->fecha_alta), $porcentaje),
            'monto_pagado' => 0,
            'estado' => DeudaCuota::ESTADO_PENDIENTE,
            'porcentaje_alta' => $porcentaje,
            'fecha_reingreso' => $hoy->toDateString(),
        ]);
    }

    /**
     * T25 (Carlos, 28/10): un inactivo que sigue inactivo solo paga deuda vieja. Pagar el mes
     * en curso o uno futuro es volver.
     */
    private function exigirActivoParaCobrarElMes(Alumno $alumno, array $data): void
    {
        if ($alumno->activo || ($data['reactivar'] ?? true)) {
            return;
        }
        $periodoActual = now()->format('Y-m');
        foreach ($data['items'] as $item) {
            if ($item['periodo'] >= $periodoActual) {
                throw new \RuntimeException('El cobro incluye el mes en curso o uno futuro: para cobrarlo, el alumno tiene que volver a activo.');
            }
        }
    }

    /**
     * T38: un cobro a un alumno inactivo lo reactiva; su cuota del mes queda como la de un
     * reingreso. Si el cobro no la incluyó, se genera; si la creó este mismo cobro, se le
     * graba el día del reingreso (plazo) y el porcentaje con que nació, para que no se
     * vuelva a calcular. Una cuota que ya existía antes del cobro no se toca.
     *
     * Sin plan para el mes no se genera nada: el cobro de la deuda vieja no debe fallar.
     */
    private function completarReingresoPorCobro(Alumno $alumno, bool $cuotaDelMesExistia, float $porcentaje, ?string $periodoConDescuento): void
    {
        $periodo = now()->format('Y-m');
        AlumnoRevisionCobranza::reactivarAuto($alumno->id, $periodo);

        if ($cuotaDelMesExistia) {
            return;
        }

        $deuda = DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', $periodo)->lockForUpdate()->first();

        if ($deuda === null) {
            if ($this->obtenerPlanParaPeriodo($alumno->id, $periodo)?->plan) {
                $this->crearCuotaReingreso($alumno);
            }
            return;
        }

        $deuda->update([
            'fecha_reingreso' => now()->toDateString(),
            'porcentaje_alta' => $deuda->porcentaje_alta ?? ($periodoConDescuento === $periodo ? $porcentaje : 100.0),
        ]);
    }

    /**
     * T58 (DU-23, Carlos 13/10): meses pasados que el dueño puede crear desde la ficha. Van
     * del mes de alta al anterior al actual, sin cuota y con plan, como los que habría creado
     * el generador del día 1.
     *
     * @return array<int, array{periodo:string, precio:float}>
     */
    public function mesesPasadosSinCuota(Alumno $alumno): array
    {
        if (!$alumno->fecha_alta) {
            return [];
        }

        $existentes = DeudaCuota::where('alumno_id', $alumno->id)->pluck('periodo')->all();
        $mes = Carbon::parse($alumno->fecha_alta)->startOfMonth();
        $actual = now()->startOfMonth();
        $meses = [];
        while ($mes->lt($actual)) {
            $periodo = $mes->format('Y-m');
            $plan = in_array($periodo, $existentes, true) ? null : $this->obtenerPlanParaPeriodo($alumno->id, $periodo);
            if ($plan?->plan) {
                $meses[] = ['periodo' => $periodo, 'precio' => $plan->plan->precioParaPeriodo($periodo, $alumno->fecha_alta)];
            }
            $mes->addMonthNoOverflow();
        }

        return $meses;
    }

    /**
     * T58: el dueño crea la cuota de un mes pasado que nunca se generó. Vale lo mismo que la
     * habría creado el generador (precio del plan de ese mes); el cobro aplica sus reglas de
     * siempre. Queda anotado quién la creó.
     */
    public function crearCuotaMesPasado(int $alumnoId, string $periodo, int $adminId): DeudaCuota
    {
        return DB::transaction(function () use ($alumnoId, $periodo, $adminId) {
            $admin = \App\Models\User::findOrFail($adminId);
            abort_unless($admin->isAdmin() && $admin->activo, 403);
            $alumno = Alumno::whereKey($alumnoId)->lockForUpdate()->firstOrFail();

            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $periodo)) {
                throw new \Exception('El mes elegido no es válido.');
            }
            if ($periodo >= now()->format('Y-m')) {
                throw new \Exception('Solo se crean desde acá las cuotas de meses que ya pasaron.');
            }
            if (!$alumno->fecha_alta || $periodo < $alumno->fecha_alta->format('Y-m')) {
                throw new \Exception('Ese mes es anterior al ingreso del alumno.');
            }
            if (DeudaCuota::where('alumno_id', $alumno->id)->where('periodo', $periodo)->lockForUpdate()->exists()) {
                throw new \Exception('Ese mes ya tiene su cuota.');
            }
            $plan = $this->obtenerPlanParaPeriodo($alumno->id, $periodo);
            if (!$plan?->plan) {
                throw new \Exception('El alumno no tenía un plan en ese mes.');
            }

            return DeudaCuota::create([
                'alumno_id' => $alumno->id,
                'periodo' => $periodo,
                'monto_original' => $plan->plan->precioParaPeriodo($periodo, $alumno->fecha_alta),
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
                'observaciones' => 'Creada por ' . $admin->name . ' el ' . now()->format('d/m/Y') . ': el mes no se había generado.',
            ]);
        });
    }

    /**
     * Aplicar pagos a las deudas del alumno.
     *
     * @param int $alumnoId
     * @param array $items [['periodo' => 'YYYY-MM', 'monto' => float], ...]
     * @param array<string, float> $montosOriginalesNuevasDeudas
     * @return array{0: array<string, DeudaCuota>, 1: array<string, float>}
     * @throws \Exception
     */
    private function aplicarPagoADeudas(
        int $alumnoId,
        array $items,
        array $montosOriginalesNuevasDeudas = [],
        ?string $fechaPago = null
    ): array
    {
        $deudasActualizadas = [];
        $montosAplicados = [];

        foreach ($items as $item) {
            $deuda = $this->obtenerOcrearDeuda(
                $alumnoId,
                $item['periodo'],
                $montosOriginalesNuevasDeudas[$item['periodo']] ?? null
            );

            if ($deuda->estado === DeudaCuota::ESTADO_PAGADA) {
                throw new \Exception("La deuda del período {$item['periodo']} ya está completamente pagada.");
            }

            if ($deuda->estado === DeudaCuota::ESTADO_CONDONADA) {
                throw new \Exception("La deuda del período {$item['periodo']} fue condonada, no admite pagos.");
            }

            $saldoPendiente = $deuda->saldo_pendiente;
            $montoSolicitado = (float) $item['monto'];

            if ($montoSolicitado > $saldoPendiente) {
                throw new \Exception(
                    "El monto para el período {$item['periodo']} supera el saldo pendiente."
                );
            }

            $montoAplicar = $montoSolicitado;

            if ($montoAplicar <= 0) {
                throw new \Exception("El monto a aplicar debe ser mayor a 0 para el período {$item['periodo']}.");
            }

            $deuda->monto_pagado = (float) $deuda->monto_pagado + $montoAplicar;

            if ($deuda->monto_pagado >= $deuda->monto_original) {
                $deuda->estado = DeudaCuota::ESTADO_PAGADA;
            }

            $deuda->fechaHistorial = $fechaPago;
            $deuda->save();
            $deudasActualizadas[$item['periodo']] = $deuda;
            $montosAplicados[$item['periodo']] = $montoAplicar;
        }

        return [$deudasActualizadas, $montosAplicados];
    }

    /**
     * Serializa todos los cobros del mismo alumno, incluso cuando la deuda
     * todavía no existe y debe crearse dentro de la transacción.
     */
    private function bloquearAlumnoParaPago(int $alumnoId): Alumno
    {
        return Alumno::whereKey($alumnoId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Obtener o crear DeudaCuota para un alumno/período.
     * Si no existe y el período es vigente o futuro, la crea automáticamente
     * usando el precio del plan activo del alumno, salvo que el primer pago
     * tenga un monto original descontado explícito.
     * Si el período es pasado, exige creación manual por admin.
     */
    private function obtenerOcrearDeuda(
        int $alumnoId,
        string $periodo,
        ?float $montoOriginalAlCrear = null
    ): DeudaCuota
    {
        $deuda = DeudaCuota::where('alumno_id', $alumnoId)
            ->where('periodo', $periodo)
            ->lockForUpdate()
            ->first();

        if ($deuda) {
            return $deuda;
        }

        // No permitir crear deuda para períodos pasados
        $periodoVigente = Carbon::now()->format('Y-m');
        if ($periodo < $periodoVigente) {
            throw new \Exception(
                "No se puede crear deuda para un período pasado ({$periodo}). La crea el dueño desde la ficha del alumno."
            );
        }

        // Buscar el plan que aplica al período por vigencia de fechas
        $alumnoPlan = $this->obtenerPlanParaPeriodo($alumnoId, $periodo);

        if (!$alumnoPlan || !$alumnoPlan->plan) {
            throw new \Exception(
                "Alumno sin plan aplicable para el período {$periodo}."
            );
        }

        $montoOriginal = $montoOriginalAlCrear ?? $alumnoPlan->plan->precioParaPeriodo($periodo, Alumno::findOrFail($alumnoId)->fecha_alta);

        // firstOrCreate para idempotencia (protege contra race conditions por unique constraint)
        return DeudaCuota::firstOrCreate(
            ['alumno_id' => $alumnoId, 'periodo' => $periodo],
            [
                'monto_original' => $montoOriginal,
                'monto_pagado' => 0,
                'estado' => DeudaCuota::ESTADO_PENDIENTE,
            ]
        );
    }

    /**
     * Obtener el AlumnoPlan que aplica a un período dado.
     * Busca por vigencia de fechas (fecha_desde/fecha_hasta), no solo por activo=true.
     * Si hay más de uno aplicable, elige el de mayor fecha_desde (más reciente).
     */
    public function obtenerPlanParaPeriodo(int $alumnoId, string $periodo): ?AlumnoPlan
    {
        $periodStart = Carbon::parse($periodo . '-01');
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Buscar plan cuya vigencia cubra el período:
        // fecha_desde <= último día del mes AND (fecha_hasta is null OR fecha_hasta >= primer día del mes)
        $alumnoPlan = AlumnoPlan::where('alumno_id', $alumnoId)
            ->where('fecha_desde', '<=', $periodEnd->toDateString())
            ->where(function ($query) use ($periodStart) {
                $query->whereNull('fecha_hasta')
                      ->orWhere('fecha_hasta', '>=', $periodStart->toDateString());
            })
            ->orderByDesc('fecha_desde')
            ->orderByDesc('id')
            ->first();

        return $alumnoPlan;
    }

    /**
     * Ordenar items por período ascendente para garantizar imputación FIFO.
     */
    private function ordenarItemsPorPeriodo(array $items): array
    {
        usort($items, fn($a, $b) => strcmp($a['periodo'], $b['periodo']));
        return $items;
    }

    /**
     * Validar regla FIFO fuerte: las deudas más viejas deben pagarse completas
     * antes de imputar a períodos posteriores. Solo la última deuda (por orden
     * cronológico) puede quedar parcial.
     */
    private function validarFifo(
        int $alumnoId,
        array $items,
        array $montosOriginalesNuevasDeudas = []
    ): void
    {
        if (count($items) <= 1) {
            return;
        }

        // Items ya vienen ordenados por periodo ASC
        for ($i = 0; $i < count($items) - 1; $i++) {
            $item = $items[$i];
            $deuda = $this->obtenerOcrearDeuda(
                $alumnoId,
                $item['periodo'],
                $montosOriginalesNuevasDeudas[$item['periodo']] ?? null
            );
            $saldoPendiente = $deuda->saldo_pendiente;

            if ($item['monto'] < $saldoPendiente) {
                throw new \Exception(
                    "FIFO: La deuda del período {$item['periodo']} tiene saldo pendiente de "
                    . "\${$saldoPendiente} y debe pagarse completa antes de imputar a períodos "
                    . "posteriores. Monto enviado: \${$item['monto']}."
                );
            }
        }
    }

    /**
     * Obtener el subrubro reservado para cuotas mensuales.
     *
     * @return Subrubro
     * @throws \Exception
     */
    private function obtenerSubrubroCuota(): Subrubro
    {
        $subrubro = Subrubro::where('nombre', 'Cuota Mensual')->first();

        if (!$subrubro) {
            throw new \Exception('Subrubro "Cuota Mensual" no encontrado. Ejecutar seeders.');
        }

        return $subrubro;
    }

    /**
     * Calcular el monto total de los items.
     */
    private function calcularMontoTotal(array $items): float
    {
        return array_sum(array_column($items, 'monto'));
    }

    /**
     * Parsear fecha, default a hoy si no viene.
     */
    private function parsearFecha(?string $fecha): string
    {
        if ($fecha) {
            return Carbon::parse($fecha)->toDateString();
        }
        return Carbon::now()->toDateString();
    }

    /**
     * Crear el registro de pago.
     */
    private function crearPago(int $alumnoId, float $montoTotal, string $fechaPago, ?string $observaciones, float $porcentaje = 100.0, ?int $reglaId = null, ?float $montoCuota = null): Pago
    {
        $fechaCarbon = Carbon::parse($fechaPago);
        $montoCuota ??= $montoTotal;
        $montoBase   = $porcentaje < 100
            ? round($montoCuota / ($porcentaje / 100), 2)
            : $montoCuota;

        return Pago::create([
            'alumno_id'            => $alumnoId,
            'plan_id'              => null,
            'regla_primer_pago_id' => $reglaId,
            'mes'                  => $fechaCarbon->month,
            'anio'                 => $fechaCarbon->year,
            'monto_base'           => $montoBase,
            'porcentaje_aplicado'  => $porcentaje,
            'monto_final'          => $montoTotal,
            'monto_cuota'          => $montoCuota,
            'fecha_pago'           => $fechaPago,
            'observaciones'        => $observaciones,
            'estado'               => Pago::ESTADO_COMPLETADO,
        ]);
    }

    /**
     * Detecta si aplica una regla de primer pago.
     *
     * Devuelve [porcentaje, reglaId, periodoConDescuento].
     *
     * El descuento existe por un motivo concreto: quien se anota el 24 de abril no
     * usó abril entero, así que no paga abril entero. **Por eso solo alcanza al mes
     * en que el alumno entró, y a ningún otro.**
     *
     * Antes no se miraba a qué mes correspondía la cuota que se estaba cobrando: se
     * aplicaba el descuento por el solo hecho de que el alumno no tuviera pagos
     * registrados. Eso rompe en dos situaciones reales:
     *
     *  - **La carga inicial de un club que ya venía funcionando.** Sus alumnos entran
     *    con fecha de alta vieja y sin ningún pago en Wings, porque lo que pagaron lo
     *    pagaron antes. Medido sobre los diez alumnos de la primera carga: cuatro se
     *    habían anotado después del día 15 y el sistema les descontaba $81.300 entre
     *    los cuatro, en su primer cobro.
     *  - **Un pago que cubre varios meses.** El descuento del mes de entrada se
     *    aplicaba también a los demás meses del mismo pago.
     *
     * Ver `Wings-contrato-estadosAlum-cobranza-asistencia-V1.md` §4.
     *
     * @param array<int,array{periodo:string,monto:mixed}> $items
     * @return array{0:float, 1:int|null, 2:string|null}
     */
    public function calcularReglaPrimerPago(int $alumnoId, array $items): array
    {
        $alumno     = Alumno::find($alumnoId);
        // Solo cuentan los pagos que de verdad ocurrieron. Un cobro cancelado deja el
        // Pago con estado ANULADO, y sin este filtro le sacaba el descuento de
        // bienvenida a un alumno al que nunca le entro plata.
        $tienePagos = Pago::where('alumno_id', $alumnoId)
            ->where('estado', Pago::ESTADO_COMPLETADO)->conCuota()
            ->exists();

        if (!$alumno) {
            return [100.0, null, null];
        }

        $periodosCobrados = array_column($items, 'periodo');

        // Caso 1: alumno sin pagos previos (nuevo) — el descuento es por el mes en
        // que se dio de alta, y solo corre si ese mes es uno de los que se cobran.
        if (!$tienePagos && $alumno->fecha_alta) {
            // Solo la cuota del alta: la de un reingreso (T38) también guarda su porcentaje,
            // pero no es el del mes en que el alumno entró.
            $porcentajeAlta = DeudaCuota::where('alumno_id', $alumnoId)->whereNotNull('porcentaje_alta')
                ->whereNull('fecha_reingreso')->value('porcentaje_alta');
            if ($porcentajeAlta !== null) {
                // Metadato del pago; período NULL impide recalcular o ajustar la deuda.
                return [(float) $porcentajeAlta, null, null];
            }
            $periodoAlta = $alumno->fecha_alta->format('Y-m');

            if (!in_array($periodoAlta, $periodosCobrados, true)) {
                return [100.0, null, null];
            }

            // Reactivado en el mismo mes del alta (T38): esa cuota ya nació con el porcentaje
            // de reingreso y no se vuelve a calcular.
            if (DeudaCuota::where('alumno_id', $alumnoId)->where('periodo', $periodoAlta)->whereNotNull('porcentaje_alta')->exists()) {
                return [100.0, null, null];
            }

            return $this->reglaDelDia($alumno->fecha_alta->day, $periodoAlta);
        }

        // Caso 2: alumno inactivo que vuelve (ya conoce el lugar) — el descuento es
        // por el mes en que vuelve, con la misma condición.
        if (!$alumno->activo && $tienePagos) {
            $hoy            = Carbon::now();
            $periodoRetorno = $hoy->format('Y-m');

            if (DeudaCuota::where('alumno_id', $alumnoId)->where('periodo', $periodoRetorno)->whereNotNull('porcentaje_alta')->exists()) {
                return [100.0, null, null];
            }

            if (!in_array($periodoRetorno, $periodosCobrados, true)) {
                return [100.0, null, null];
            }

            return $this->reglaDelDia($hoy->day, $periodoRetorno);
        }

        return [100.0, null, null];
    }

    /**
     * Busca la regla del día. Si hay cero o más de una, no se descuenta nada.
     *
     * @return array{0:float, 1:int|null, 2:string|null}
     */
    private function reglaDelDia(int $dia, string $periodo): array
    {
        $reglas = ReglaPrimerPago::obtenerReglaPorDia($dia);

        if ($reglas->count() === 1) {
            return [(float) $reglas->first()->porcentaje, $reglas->first()->id, $periodo];
        }

        return [100.0, null, null];
    }

    /**
     * Escala el monto del período que lleva el descuento. El resto queda intacto.
     */
    /**
     * Precio del mes de alta ya descontado.
     *
     * La base es el precio de lista del plan que rige ese mes; solo si no hay plan se usa
     * el monto de la deuda. Nunca el importe que el operativo esta pagando.
     *
     * Publica a proposito: la pantalla de cobro tiene que mostrar exactamente este
     * numero. Cuando cada lado hacia su propia cuenta salieron COB-06, COB-07, COB-09 y la
     * revision de COB-04, siempre igual — dos versiones del mismo importe.
     */
    public function precioConDescuento(int $alumnoId, string $periodo, float $porcentaje): float
    {
        // La base es el precio de lista del mes, no el monto de la deuda: esa puede venir
        // ya descontada de un cobro que se cancelo, y el descuento se aplicaria dos veces.
        $alumnoPlan = $this->obtenerPlanParaPeriodo($alumnoId, $periodo);

        if ($alumnoPlan?->plan) {
            $base = $alumnoPlan->plan->precioParaPeriodo($periodo, Alumno::findOrFail($alumnoId)->fecha_alta);
        } else {
            $deuda = DeudaCuota::where('alumno_id', $alumnoId)->where('periodo', $periodo)->first();
            if (!$deuda) {
                throw new \Exception("Alumno sin plan aplicable para el período {$periodo}.");
            }
            $base = (float) $deuda->monto_original;
        }

        return $this->aplicarPorcentaje($base, $porcentaje);
    }

    /**
     * La unica formula del descuento de primer pago. La pantalla la usa para anunciar el
     * precio de cada plan posible, asi que no puede haber una segunda cuenta en otro lado.
     */
    public function aplicarPorcentaje(float $precio, float $porcentaje): float
    {
        return round($precio * ($porcentaje / 100), 2);
    }

    /**
     * Recorta el importe del mes de alta al saldo que quedo despues del descuento.
     *
     * Sin esto, quien paga la cuota entera envia el precio de lista y `aplicarPagoADeudas`
     * lo rechaza por superar el saldo ya descontado.
     */
    private function limitarAlSaldoConDescuento(array $items, int $alumnoId, string $periodo, float $precioConDescuento): array
    {
        $deuda = DeudaCuota::where('alumno_id', $alumnoId)->where('periodo', $periodo)->first();
        $tope = $deuda
            ? max((float) $deuda->monto_original - (float) $deuda->monto_pagado, 0)
            : $precioConDescuento;

        return array_map(fn($item) => [
            'periodo' => $item['periodo'],
            'monto'   => $item['periodo'] === $periodo
                ? min((float) $item['monto'], $tope)
                : (float) $item['monto'],
        ], $items);
    }

    /**
     * Ajusta monto_original de la deuda del mes de alta al monto con descuento.
     * Necesario para que quede PAGADA correctamente cuando el monto aplicado coincide.
     *
     * Toca un solo periodo a proposito. Antes recorria todos los items del cobro, y un
     * parcial de otro mes se quedaba con la seña como monto original: la deuda se
     * marcaba PAGADA y el saldo restante desaparecia.
     */
    private function ajustarDeudaConDescuento(int $alumnoId, string $periodo, float $monto, string $fechaPago): void
    {
        $deuda = DeudaCuota::where('alumno_id', $alumnoId)
            ->where('periodo', $periodo)
            ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
            ->whereRaw('monto_pagado < ?', [$monto])
            ->lockForUpdate()->first();
        if ($deuda) $deuda->fechaHistorial = $fechaPago;
        $actualizado = $deuda?->update(['monto_original' => $monto]);

        if (!$actualizado) {
            Log::warning('ajustarDeudaConDescuento: omitida para alumno '.$alumnoId.' período '.$periodo.' (monto_pagado >= monto descontado '.$monto.')');
        }
    }

    /**
     * Relacionar el pago con las deudas en la tabla pivote.
     */
    private function relacionarPagoConDeudas(
        Pago $pago,
        array $items,
        array $deudasActualizadas,
        array $montosAplicados
    ): void
    {
        foreach ($items as $item) {
            $deuda = $deudasActualizadas[$item['periodo']] ?? null;
            if ($deuda) {
                PagoDeudaCuota::create([
                    'pago_id' => $pago->id,
                    'deuda_cuota_id' => $deuda->id,
                    'monto_aplicado' => $montosAplicados[$item['periodo']],
                ]);
            }
        }
        // Pago::created ocurre antes de adjuntar las imputaciones; congelar el estado completo.
        app(HistorialAnaliticoReportesService::class)->registrar($pago);
    }

    /**
     * Generar texto de observaciones para el movimiento.
     */
    private function generarObservacionesPago(int $alumnoId, array $items, ?string $observaciones): string
    {
        $periodos = implode(', ', array_column($items, 'periodo'));
        $texto = "Pago cuota alumno #{$alumnoId} - Períodos: {$periodos}";
        if ($observaciones) {
            $texto .= " - {$observaciones}";
        }
        return $texto;
    }

    /**
     * Cancelar un cobro de cuota operativo.
     * Revierte el monto de las deudas, anula el pago y marca el movimiento como CANCELADO.
     * Solo funciona si la caja está ABIERTA.
     * Solo funciona en movimientos que tengan pago_id (cobros registrados con el nuevo flujo).
     *
     * Nota: pago.estado pasa a 'ANULADO' (estado agregado via migración 2026_06_21).
     *
     * @throws \Exception
     */
    public function cancelarCobroOperativo(int $movimientoId, string $motivo, int $usuarioId): void
    {
        DB::transaction(function () use ($movimientoId, $motivo, $usuarioId) {
            // Esta primera lectura solo localiza filas; ningún estado se decide aquí.
            $referencia = MovimientoOperativo::findOrFail($movimientoId);
            $alumnoId = $referencia->alumno_id
                ?? Pago::whereKey($referencia->pago_id)->value('alumno_id');
            if ($alumnoId) {
                app(InscripcionService::class)->bloquear(Alumno::findOrFail($alumnoId)->dni, false);
                Alumno::whereKey($alumnoId)->lockForUpdate()->firstOrFail();
            }
            $caja = \App\Models\CajaOperativa::whereKey($referencia->caja_operativa_id)
                ->lockForUpdate()->firstOrFail();
            $movimiento = MovimientoOperativo::whereKey($movimientoId)->lockForUpdate()->firstOrFail();

            if (!$movimiento->pago_id) {
                throw new \Exception('Este cobro no tiene registro de pago vinculado. No se puede cancelar automáticamente.');
            }

            if (!in_array($caja->estado, ['ABIERTA', 'RECHAZADA'])) {
                throw new \Exception('Solo se puede cancelar un cobro mientras la caja esté abierta o rechazada.');
            }

            if ($movimiento->estado === 'CANCELADO') {
                throw new \Exception('Este movimiento ya fue cancelado.');
            }

            $pago = Pago::whereKey($movimiento->pago_id)->lockForUpdate()->firstOrFail();

            $pagoDeudas = PagoDeudaCuota::query()
                ->where('pago_id', $pago->id)
                ->orderBy('deuda_cuota_id')->lockForUpdate()
                ->get();

            foreach ($pagoDeudas as $pd) {
                $pd->setRelation('deudaCuota', DeudaCuota::whereKey($pd->deuda_cuota_id)
                    ->lockForUpdate()->firstOrFail());
            }

            // Conservar el detalle antes de retirar las imputaciones activas.
            // Se guarda en la misma transaccion que la reversion del cobro.
            $pago->detalle_anulacion = [
                'motivo' => $motivo,
                'cargos' => $pago->cargos->map(fn ($c) => [...($c->tipo === 'PUNITORIO' ? ['cargo_alumno_id' => $c->id] : []), 'periodo' => $c->deudaCuota?->periodo ?? '', 'periodo_texto' => $c->tipo === 'PUNITORIO' ? $c->descripcion : 'Inscripción al club', 'monto_aplicado' => $c->pivot->monto_aplicado])->all(),
                'periodos' => $pagoDeudas->map(fn ($pd) => [
                    'periodo' => $pd->deudaCuota->periodo,
                    'monto_aplicado' => $pd->monto_aplicado,
                ])->all(),
            ];

            foreach ($pagoDeudas as $pd) {
                $deuda = $pd->deudaCuota;

                $deuda->monto_pagado = max(0, (float) $deuda->monto_pagado - (float) $pd->monto_aplicado);

                // DeudaCuota no tiene estado PARCIAL — después de revertir siempre es PENDIENTE
                // salvo que haya otro pago anterior que la haya dejado pagada
                if ((float) $deuda->monto_pagado >= (float) $deuda->monto_original) {
                    $deuda->estado = DeudaCuota::ESTADO_PAGADA;
                } else {
                    $deuda->estado = DeudaCuota::ESTADO_PENDIENTE;
                }

                $deuda->observaciones = $this->agregarObservacion(
                    $deuda->observaciones,
                    "[CANCELADO] Pago #{$pago->id} revertido - {$motivo}"
                );

                $deuda->save();
            }

            PagoDeudaCuota::where('pago_id', $pago->id)->delete();
            $pago->cargos()->detach();

            $pago->estado = 'ANULADO';
            $pago->save();

            MovimientoOperativo::where('pago_id', $pago->id)->update(['estado' => 'CANCELADO', 'motivo_cancelacion' => $motivo]);
        });
    }

    /**
     * Anular un cobro hecho por el ADMIN, que no pasa por ninguna caja.
     *
     * A13/B1: desde el 05/10/2026 el dueño cobra directo al cashflow. Anular vivía sólo
     * dentro de la caja del mostrador (`cancelarCobroOperativo`), así que un cobro suyo mal
     * hecho —medio equivocado, importe de más— quedaba sin forma de corregirse desde la
     * pantalla. Revierte lo mismo que el otro camino y además saca la plata del cashflow
     * con un asiento en contra, para no borrar lo que pasó.
     */
    public function anularCobroAdmin(int $pagoId, string $motivo, int $adminId): void
    {
        DB::transaction(function () use ($pagoId, $motivo, $adminId) {
            $pago = Pago::whereKey($pagoId)->lockForUpdate()->firstOrFail();

            if ($pago->estado === 'ANULADO') {
                throw new \Exception('Este cobro ya fue anulado.');
            }

            if (MovimientoOperativo::where('pago_id', $pago->id)->exists()) {
                throw new \Exception('Este cobro se hizo por caja: se anula desde el detalle de esa caja.');
            }

            app(InscripcionService::class)->bloquear(Alumno::findOrFail($pago->alumno_id)->dni, false);
            Alumno::whereKey($pago->alumno_id)->lockForUpdate()->firstOrFail();

            $pagoDeudas = PagoDeudaCuota::where('pago_id', $pago->id)
                ->orderBy('deuda_cuota_id')->lockForUpdate()->get();

            foreach ($pagoDeudas as $pd) {
                $pd->setRelation('deudaCuota', DeudaCuota::whereKey($pd->deuda_cuota_id)
                    ->lockForUpdate()->firstOrFail());
            }

            // El detalle se guarda antes de retirar las imputaciones: el recibo anulado
            // tiene que seguir diciendo qué periodos cubría y por cuánto.
            $pago->detalle_anulacion = [
                'motivo' => $motivo,
                'cargos' => $pago->cargos->map(fn ($c) => [
                    ...($c->tipo === 'PUNITORIO' ? ['cargo_alumno_id' => $c->id] : []),
                    'periodo' => $c->deudaCuota?->periodo ?? '',
                    'periodo_texto' => $c->tipo === 'PUNITORIO' ? $c->descripcion : 'Inscripción al club',
                    'monto_aplicado' => $c->pivot->monto_aplicado,
                ])->all(),
                'periodos' => $pagoDeudas->map(fn ($pd) => [
                    'periodo' => $pd->deudaCuota->periodo,
                    'monto_aplicado' => $pd->monto_aplicado,
                ])->all(),
            ];

            // T39 (DU-21, Carlos 13/10): la anulación lleva la fecha del cobro original, como al
            // eliminar un movimiento directo en Cashflow. Plata y deuda vuelven en el mismo mes:
            // el mes del cobro queda como si ese cobro no hubiera existido.
            $fechaOriginal = Carbon::parse($pago->fecha_pago)->toDateString();

            foreach ($pagoDeudas as $pd) {
                $deuda = $pd->deudaCuota;
                $deuda->fechaHistorial = $fechaOriginal;
                $deuda->monto_pagado = max(0, (float) $deuda->monto_pagado - (float) $pd->monto_aplicado);
                $deuda->estado = (float) $deuda->monto_pagado >= (float) $deuda->monto_original
                    ? DeudaCuota::ESTADO_PAGADA
                    : DeudaCuota::ESTADO_PENDIENTE;
                $deuda->observaciones = $this->agregarObservacion(
                    $deuda->observaciones,
                    "[ANULADO] Pago #{$pago->id} revertido - {$motivo}"
                );
                $deuda->save();
            }

            PagoDeudaCuota::where('pago_id', $pago->id)->delete();
            $pago->cargos()->detach();

            $pago->estado = 'ANULADO';
            $pago->save();

            // El asiento original no se borra: se le pone uno en contra, así el cashflow
            // cuenta lo que pasó de verdad y el saldo del medio de pago queda bien.
            $originales = CashflowMovimiento::where('referencia_tipo', CashflowMovimiento::REF_PAGO_CUOTA)
                ->where('referencia_id', $pago->id)
                ->lockForUpdate()
                ->get();

            foreach ($originales as $original) {
                if ((float) $original->monto <= 0) {
                    continue;
                }

                \App\Support\ClasificacionSubrubros::exigirSinTraspaso($original->subrubro, $original->reporte_clasificacion);
                CashflowMovimiento::create([
                    'fecha' => $original->fecha->toDateString(),
                    'subrubro_id' => $original->subrubro_id,
                    'tipo_caja_id' => $original->tipo_caja_id,
                    'monto' => -1 * (float) $original->monto,
                    'reporte_deporte_id' => $original->reporte_deporte_id,
                    'reporte_clasificacion' => $original->reporte_clasificacion,
                    'reporte_tipo' => $original->reporte_tipo,
                    'observaciones' => "Anulación del cobro #{$pago->id} - {$motivo}",
                    'usuario_admin_id' => $adminId,
                    'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA,
                    'referencia_id' => $pago->id,
                ]);
            }
        });
    }

    /**
     * Inscripción primero; después cada mes, capital y su recargo, de viejo a nuevo.
     */
    private function distribuirInscripcion(array $data, array $items): array
    {
        $cargo = app(InscripcionService::class)->cargo(Alumno::findOrFail($data['alumno_id'])->dni);
        $saldo = $cargo?->saldo_pendiente ?? 0;
        $deudas = DeudaCuota::where('alumno_id', $data['alumno_id'])
            ->whereIn('periodo', array_column($items, 'periodo'))->orderBy('periodo')
            ->lockForUpdate()->get()->keyBy('periodo');
        $cargos = \App\Models\CargoAlumno::where('tipo', 'PUNITORIO')->where('estado', 'VIGENTE')
            ->where('alumno_id', $data['alumno_id'])->whereIn('deuda_cuota_id', $deudas->pluck('id'))
            ->orderBy('deuda_cuota_id')->lockForUpdate()->get()->keyBy('deuda_cuota_id');
        $saldosRecargos = $cargos->map(fn ($c) => max(0, $c->saldo_pendiente));
        $totalCuotas = $this->calcularMontoTotal($items);
        $recargosCobrables = 0;
        foreach ($items as $item) {
            $deuda = $deudas->get($item['periodo']);
            if ($deuda && ($deuda->estado === DeudaCuota::ESTADO_CONDONADA || (float) $item['monto'] >= $deuda->saldo_pendiente)) {
                $recargosCobrables += $saldosRecargos->get($deuda->id, 0);
            }
        }
        $maximo = round($totalCuotas + $saldo + $recargosCobrables, 2);
        $entregado = round((float) ($data['monto_entregado'] ?? $maximo), 2);
        if ($entregado <= 0 || $entregado > $maximo) {
            throw new \RuntimeException('El importe entregado debe ser positivo y no superar la deuda seleccionada más la inscripción.');
        }
        $inscripcion = min($entregado, $saldo);
        $resto = round($entregado - $inscripcion, 2);
        $cuotas = [];
        $punitorios = [];
        foreach ($items as $item) {
            $deuda = $deudas->get($item['periodo']);
            $punitorio = $deuda ? $cargos->get($deuda->id) : null;
            $saldoCapital = $deuda && $deuda->estado === DeudaCuota::ESTADO_CONDONADA ? 0 : $deuda?->saldo_pendiente;
            $monto = min($resto, max(0, (float) $item['monto']));
            if ($monto > 0) $cuotas[] = ['periodo' => $item['periodo'], 'monto' => $monto];
            $resto = round($resto - $monto, 2);
            $saldoRecargo = $punitorio ? $saldosRecargos->get($deuda->id, 0) : 0;
            if ($saldoRecargo > 0 && $resto > 0) {
                // No saltear capital limitando desde el cliente el importe de su cuota.
                if ($monto < $saldoCapital) {
                    throw new \RuntimeException('Completá la cuota del mes antes de cobrar su recargo o pasar al siguiente mes.');
                }
                $aplicado = min($resto, $saldoRecargo);
                $punitorios[] = ['cargo' => $punitorio, 'monto' => $aplicado];
                $resto = round($resto - $aplicado, 2);
            }
        }
        if ($resto > 0) throw new \RuntimeException('El importe entregado supera los importes que se pueden cobrar.');
        return [$cuotas, $cargo, $inscripcion, $punitorios];
    }

    private function agregarObservacion(?string $existente, string $nueva): string
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $nuevaConFecha = "[{$timestamp}] {$nueva}";

        if ($existente) {
            return $existente . "\n" . $nuevaConFecha;
        }
        return $nuevaConFecha;
    }
}
