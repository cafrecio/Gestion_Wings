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
            $this->bloquearAlumnoParaPago($data['alumno_id']);

            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $items = $this->ordenarItemsPorPeriodo($data['items']);
            $montosOriginalesNuevasDeudas = [];

            // Regla de primer pago: ajustar montos si aplica
            [$porcentaje, $reglaId] = $this->calcularReglaPrimerPago($data['alumno_id']);
            if ($porcentaje < 100) {
                $items = $this->aplicarPorcentajeAItems($items, $porcentaje);
                $montosOriginalesNuevasDeudas = array_column($items, 'monto', 'periodo');
                $this->ajustarDeudas($data['alumno_id'], $items);
            }

            $montoTotal = $this->calcularMontoTotal($items);

            // Validar FIFO antes de aplicar pagos
            $this->validarFifo($data['alumno_id'], $items, $montosOriginalesNuevasDeudas);

            // Validar y procesar deudas (auto-crea DeudaCuota si no existe)
            [$deudasActualizadas, $montosAplicados] = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $items,
                $montosOriginalesNuevasDeudas
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null,
                $porcentaje,
                $reglaId
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $items, $deudasActualizadas, $montosAplicados);

            // Si el alumno estaba inactivo o en revisión, reactivar al pagar
            Alumno::where('id', $data['alumno_id'])->where('activo', false)->update(['activo' => true]);
            foreach ($items as $item) {
                AlumnoRevisionCobranza::reactivarAuto($data['alumno_id'], $item['periodo']);
            }

            // Crear movimiento operativo (abre caja si no existe)
            // Usa método interno para permitir subrubro reservado "Cuota Mensual"
            $movimiento = $this->cajaService->registrarMovimientoOperativoInterno([
                'usuario_operativo_id' => $data['usuario_operativo_id'],
                'tipo_caja_id'         => $data['tipo_caja_id'],
                'subrubro_id'          => $subruboCuota->id,
                'monto'                => $montoTotal,
                'fecha'                => $fechaPago,
                'observaciones'        => $this->generarObservacionesPago($data['alumno_id'], $items, $data['observaciones'] ?? null),
                'alumno_id'            => $data['alumno_id'],
                'pago_id'              => $pago->id,
            ]);

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
            $this->bloquearAlumnoParaPago($data['alumno_id']);

            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $items = $this->ordenarItemsPorPeriodo($data['items']);
            $montosOriginalesNuevasDeudas = [];

            // Regla de primer pago: ajustar montos si aplica
            [$porcentaje, $reglaId] = $this->calcularReglaPrimerPago($data['alumno_id']);
            if ($porcentaje < 100) {
                $items = $this->aplicarPorcentajeAItems($items, $porcentaje);
                $montosOriginalesNuevasDeudas = array_column($items, 'monto', 'periodo');
                $this->ajustarDeudas($data['alumno_id'], $items);
            }

            $montoTotal = $this->calcularMontoTotal($items);

            // Validar FIFO antes de aplicar pagos
            $this->validarFifo($data['alumno_id'], $items, $montosOriginalesNuevasDeudas);

            // Validar y procesar deudas (auto-crea DeudaCuota si no existe)
            [$deudasActualizadas, $montosAplicados] = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $items,
                $montosOriginalesNuevasDeudas
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null,
                $porcentaje,
                $reglaId
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $items, $deudasActualizadas, $montosAplicados);

            // Si el alumno estaba inactivo o en revisión, reactivar al pagar
            Alumno::where('id', $data['alumno_id'])->where('activo', false)->update(['activo' => true]);
            foreach ($items as $item) {
                AlumnoRevisionCobranza::reactivarAuto($data['alumno_id'], $item['periodo']);
            }

            // Crear movimiento en cashflow directo
            $movimiento = CashflowMovimiento::create([
                'fecha' => $fechaPago,
                'subrubro_id' => $subruboCuota->id,
                'tipo_caja_id' => $data['tipo_caja_id'],
                'monto' => $montoTotal,
                'observaciones' => $this->generarObservacionesPago($data['alumno_id'], $items, $data['observaciones'] ?? null),
                'usuario_admin_id' => $data['usuario_admin_id'],
                'referencia_tipo' => CashflowMovimiento::REF_PAGO_CUOTA,
                'referencia_id' => $pago->id,
            ]);

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

        $deuda = DeudaCuota::findOrFail($deudaId);

        if ($deuda->estado !== DeudaCuota::ESTADO_PENDIENTE) {
            throw new \Exception("Solo se pueden condonar deudas con estado PENDIENTE. Estado actual: {$deuda->estado}");
        }

        $deuda->estado = DeudaCuota::ESTADO_CONDONADA;
        $deuda->observaciones = $this->agregarObservacion(
            $deuda->observaciones,
            "CONDONADA por admin ID:{$adminId} - {$observaciones}"
        );
        $deuda->save();

        return $deuda;
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
        array $montosOriginalesNuevasDeudas = []
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
                "No se puede crear deuda para un período pasado ({$periodo}). Debe crearla un administrador."
            );
        }

        // Buscar el plan que aplica al período por vigencia de fechas
        $alumnoPlan = $this->obtenerPlanParaPeriodo($alumnoId, $periodo);

        if (!$alumnoPlan || !$alumnoPlan->plan) {
            throw new \Exception(
                "Alumno sin plan aplicable para el período {$periodo}."
            );
        }

        $montoOriginal = $montoOriginalAlCrear ?? (float) $alumnoPlan->plan->precio_mensual;

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
    private function crearPago(int $alumnoId, float $montoTotal, string $fechaPago, ?string $observaciones, float $porcentaje = 100.0, ?int $reglaId = null): Pago
    {
        $fechaCarbon = Carbon::parse($fechaPago);
        $montoBase   = $porcentaje < 100
            ? round($montoTotal / ($porcentaje / 100), 2)
            : $montoTotal;

        return Pago::create([
            'alumno_id'            => $alumnoId,
            'plan_id'              => null,
            'regla_primer_pago_id' => $reglaId,
            'mes'                  => $fechaCarbon->month,
            'anio'                 => $fechaCarbon->year,
            'monto_base'           => $montoBase,
            'porcentaje_aplicado'  => $porcentaje,
            'monto_final'          => $montoTotal,
            'fecha_pago'           => $fechaPago,
            'observaciones'        => $observaciones,
            'estado'               => Pago::ESTADO_COMPLETADO,
        ]);
    }

    /**
     * Detecta si aplica una regla de primer pago y devuelve [porcentaje, reglaId].
     * Solo aplica cuando el alumno no tiene ningún Pago previo.
     */
    private function calcularReglaPrimerPago(int $alumnoId): array
    {
        $alumno     = Alumno::find($alumnoId);
        $tienePagos = Pago::where('alumno_id', $alumnoId)->exists();

        if (!$alumno) {
            return [100.0, null];
        }

        // Caso 1: alumno sin pagos previos (nuevo) — usa día de fecha_alta
        if (!$tienePagos && $alumno->fecha_alta) {
            $reglas = ReglaPrimerPago::obtenerReglaPorDia($alumno->fecha_alta->day);
            if ($reglas->count() === 1) {
                return [(float) $reglas->first()->porcentaje, $reglas->first()->id];
            }
            return [100.0, null];
        }

        // Caso 2: alumno inactivo que vuelve (ya conoce el lugar) — usa día de hoy
        if (!$alumno->activo && $tienePagos) {
            $diaHoy = Carbon::now()->day;
            $reglas  = ReglaPrimerPago::obtenerReglaPorDia($diaHoy);
            if ($reglas->count() === 1) {
                return [(float) $reglas->first()->porcentaje, $reglas->first()->id];
            }
            return [100.0, null];
        }

        return [100.0, null];
    }

    /**
     * Escala los montos de los items por un porcentaje.
     */
    private function aplicarPorcentajeAItems(array $items, float $porcentaje): array
    {
        $factor = $porcentaje / 100;
        return array_map(fn($item) => [
            'periodo' => $item['periodo'],
            'monto'   => round((float) $item['monto'] * $factor, 2),
        ], $items);
    }

    /**
     * Ajusta monto_original de deudas existentes al monto descontado (primer pago).
     * Necesario para que queden PAGADAS correctamente cuando el monto aplicado coincide.
     */
    private function ajustarDeudas(int $alumnoId, array $items): void
    {
        foreach ($items as $item) {
            $actualizado = DeudaCuota::where('alumno_id', $alumnoId)
                ->where('periodo', $item['periodo'])
                ->where('estado', DeudaCuota::ESTADO_PENDIENTE)
                ->whereRaw('monto_pagado < ?', [$item['monto']])
                ->update(['monto_original' => $item['monto']]);

            if (!$actualizado) {
                Log::warning('ajustarDeudas: omitida para alumno '.$alumnoId.' período '.$item['periodo'].' (monto_pagado >= monto descontado '.$item['monto'].')');
            }
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
            $movimiento = MovimientoOperativo::with(['cajaOperativa'])->findOrFail($movimientoId);

            if (!$movimiento->pago_id) {
                throw new \Exception('Este cobro no tiene registro de pago vinculado. No se puede cancelar automáticamente.');
            }

            if (!in_array($movimiento->cajaOperativa->estado, ['ABIERTA', 'RECHAZADA'])) {
                throw new \Exception('Solo se puede cancelar un cobro mientras la caja esté abierta o rechazada.');
            }

            if ($movimiento->estado === 'CANCELADO') {
                throw new \Exception('Este movimiento ya fue cancelado.');
            }

            $pago = Pago::findOrFail($movimiento->pago_id);

            $pagoDeudas = PagoDeudaCuota::with('deudaCuota')
                ->where('pago_id', $pago->id)
                ->get();

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

            $pago->estado = 'ANULADO';
            $pago->save();

            $movimiento->estado = 'CANCELADO';
            $movimiento->motivo_cancelacion = $motivo;
            $movimiento->save();
        });
    }

    /**
     * Agregar observación a texto existente con timestamp.
     */
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
