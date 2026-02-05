<?php

namespace App\Services;

use App\Models\CashflowMovimiento;
use App\Models\DeudaCuota;
use App\Models\MovimientoOperativo;
use App\Models\Pago;
use App\Models\PagoDeudaCuota;
use App\Models\Subrubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $montoTotal = $this->calcularMontoTotal($data['items']);

            // Validar y procesar deudas
            $deudasActualizadas = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $data['items']
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $data['items'], $deudasActualizadas);

            // Crear movimiento operativo (abre caja si no existe)
            // Usa método interno para permitir subrubro reservado "Cuota Mensual"
            $movimiento = $this->cajaService->registrarMovimientoOperativoInterno([
                'usuario_operativo_id' => $data['usuario_operativo_id'],
                'tipo_caja_id' => $data['tipo_caja_id'],
                'subrubro_id' => $subruboCuota->id,
                'monto' => $montoTotal,
                'fecha' => $fechaPago,
                'observaciones' => $this->generarObservacionesPago($data['alumno_id'], $data['items'], $data['observaciones'] ?? null),
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
            $subruboCuota = $this->obtenerSubrubroCuota();
            $fechaPago = $this->parsearFecha($data['fecha_pago'] ?? null);
            $montoTotal = $this->calcularMontoTotal($data['items']);

            // Validar y procesar deudas
            $deudasActualizadas = $this->aplicarPagoADeudas(
                $data['alumno_id'],
                $data['items']
            );

            // Crear el pago
            $pago = $this->crearPago(
                $data['alumno_id'],
                $montoTotal,
                $fechaPago,
                $data['observaciones'] ?? null
            );

            // Relacionar pago con deudas
            $this->relacionarPagoConDeudas($pago, $data['items'], $deudasActualizadas);

            // Crear movimiento en cashflow directo
            $movimiento = CashflowMovimiento::create([
                'fecha' => $fechaPago,
                'subrubro_id' => $subruboCuota->id,
                'tipo_caja_id' => $data['tipo_caja_id'],
                'monto' => $montoTotal,
                'observaciones' => $this->generarObservacionesPago($data['alumno_id'], $data['items'], $data['observaciones'] ?? null),
                'usuario_admin_id' => $data['usuario_admin_id'],
                'referencia_tipo' => 'PAGO_CUOTA',
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
     * @return DeudaCuota[]
     * @throws \Exception
     */
    private function aplicarPagoADeudas(int $alumnoId, array $items): array
    {
        $deudasActualizadas = [];

        foreach ($items as $item) {
            $deuda = DeudaCuota::where('alumno_id', $alumnoId)
                ->where('periodo', $item['periodo'])
                ->first();

            if (!$deuda) {
                throw new \Exception("No existe deuda para el alumno {$alumnoId} en período {$item['periodo']}. Primero debe generarse la deuda.");
            }

            if ($deuda->estado === DeudaCuota::ESTADO_PAGADA) {
                throw new \Exception("La deuda del período {$item['periodo']} ya está completamente pagada.");
            }

            if ($deuda->estado === DeudaCuota::ESTADO_CONDONADA) {
                throw new \Exception("La deuda del período {$item['periodo']} fue condonada, no admite pagos.");
            }

            $saldoPendiente = $deuda->saldo_pendiente;
            $montoAplicar = min($item['monto'], $saldoPendiente);

            if ($montoAplicar <= 0) {
                throw new \Exception("El monto a aplicar debe ser mayor a 0 para el período {$item['periodo']}.");
            }

            $deuda->monto_pagado = (float) $deuda->monto_pagado + $montoAplicar;

            if ($deuda->monto_pagado >= $deuda->monto_original) {
                $deuda->estado = DeudaCuota::ESTADO_PAGADA;
            }

            $deuda->save();
            $deudasActualizadas[$item['periodo']] = $deuda;
        }

        return $deudasActualizadas;
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
        return Carbon::now('America/Argentina/Buenos_Aires')->toDateString();
    }

    /**
     * Crear el registro de pago.
     */
    private function crearPago(int $alumnoId, float $montoTotal, string $fechaPago, ?string $observaciones): Pago
    {
        $fechaCarbon = Carbon::parse($fechaPago);

        return Pago::create([
            'alumno_id' => $alumnoId,
            'plan_id' => null,
            'regla_primer_pago_id' => null,
            'mes' => $fechaCarbon->month,
            'anio' => $fechaCarbon->year,
            'monto_base' => $montoTotal,
            'porcentaje_aplicado' => 100,
            'monto_final' => $montoTotal,
            'forma_pago_id' => null,
            'fecha_pago' => $fechaPago,
            'observaciones' => $observaciones,
            'estado' => 'COMPLETADO',
        ]);
    }

    /**
     * Relacionar el pago con las deudas en la tabla pivote.
     */
    private function relacionarPagoConDeudas(Pago $pago, array $items, array $deudasActualizadas): void
    {
        foreach ($items as $item) {
            $deuda = $deudasActualizadas[$item['periodo']] ?? null;
            if ($deuda) {
                PagoDeudaCuota::create([
                    'pago_id' => $pago->id,
                    'deuda_cuota_id' => $deuda->id,
                    'monto_aplicado' => $item['monto'],
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
     * Agregar observación a texto existente con timestamp.
     */
    private function agregarObservacion(?string $existente, string $nueva): string
    {
        $timestamp = Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d H:i:s');
        $nuevaConFecha = "[{$timestamp}] {$nueva}";

        if ($existente) {
            return $existente . "\n" . $nuevaConFecha;
        }
        return $nuevaConFecha;
    }
}
