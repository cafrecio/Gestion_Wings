<?php

namespace App\Services;

use App\Models\CajaOperativa;
use App\Models\MovimientoOperativo;
use App\Models\Subrubro;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CajaService
{
    private ?CashflowIntegracionCajaService $cashflowIntegracion = null;

    /**
     * Obtener el servicio de integración cashflow (lazy load).
     */
    private function getCashflowIntegracion(): CashflowIntegracionCajaService
    {
        if ($this->cashflowIntegracion === null) {
            $this->cashflowIntegracion = app(CashflowIntegracionCajaService::class);
        }
        return $this->cashflowIntegracion;
    }

    /**
     * Obtener la caja ABIERTA del usuario o null
     *
     * @param int $usuarioOperativoId
     * @return CajaOperativa|null
     */
    public function obtenerCajaAbierta(int $usuarioOperativoId): ?CajaOperativa
    {
        return CajaOperativa::where('usuario_operativo_id', $usuarioOperativoId)
            ->where('estado', 'ABIERTA')
            ->first();
    }

    /**
     * Validar que no exista una caja vieja abierta (de día anterior)
     *
     * Si el usuario tiene una caja ABIERTA cuya apertura_at no es del día actual,
     * lanza excepción bloqueando la operación.
     *
     * @param int $usuarioOperativoId
     * @throws \Exception
     */
    public function validarCajaViejaAbierta(int $usuarioOperativoId): void
    {
        $cajaAbierta = $this->obtenerCajaAbierta($usuarioOperativoId);

        if ($cajaAbierta && !$cajaAbierta->apertura_at->isToday()) {
            throw new \Exception(
                'Tenés una caja abierta de un día anterior. No podés operar hasta cerrarla.'
            );
        }
    }

    /**
     * Abrir caja si no existe una ABIERTA para el usuario
     *
     * Si ya existe una caja ABIERTA, la retorna.
     * Antes de abrir, valida que no haya caja vieja abierta.
     *
     * @param int $usuarioOperativoId
     * @return CajaOperativa
     * @throws \Exception
     */
    public function abrirCajaSiNoExiste(int $usuarioOperativoId): CajaOperativa
    {
        // Validar caja vieja antes de cualquier operación
        $this->validarCajaViejaAbierta($usuarioOperativoId);

        // Buscar caja abierta existente
        $cajaAbierta = $this->obtenerCajaAbierta($usuarioOperativoId);

        if ($cajaAbierta) {
            return $cajaAbierta;
        }

        // Crear nueva caja
        return CajaOperativa::create([
            'usuario_operativo_id' => $usuarioOperativoId,
            'apertura_at' => Carbon::now(),
            'estado' => 'ABIERTA',
        ]);
    }

    /**
     * Registrar un movimiento operativo (uso manual por operativo).
     *
     * Abre caja automáticamente si no existe.
     * Valida que el subrubro permita OPERATIVO.
     * BLOQUEA subrubros reservados del sistema.
     *
     * @param array $data [usuario_operativo_id, tipo_caja_id, subrubro_id, monto, observaciones?, fecha?]
     * @return MovimientoOperativo
     * @throws \Exception
     */
    public function registrarMovimientoOperativo(array $data): MovimientoOperativo
    {
        $subrubro = Subrubro::findOrFail($data['subrubro_id']);

        // Bloquear subrubros reservados del sistema
        if ($subrubro->es_reservado_sistema) {
            throw new \Exception(
                "El subrubro '{$subrubro->nombre}' es reservado del sistema y no puede usarse manualmente. Use el flujo correspondiente."
            );
        }

        return $this->registrarMovimientoOperativoInterno($data);
    }

    /**
     * Registrar un movimiento operativo (uso interno del sistema).
     *
     * Permite todos los subrubros de tipo OPERATIVO, incluyendo reservados.
     * Solo debe ser llamado por otros services del sistema.
     *
     * @param array $data [usuario_operativo_id, tipo_caja_id, subrubro_id, monto, observaciones?, fecha?]
     * @return MovimientoOperativo
     * @throws \Exception
     */
    public function registrarMovimientoOperativoInterno(array $data): MovimientoOperativo
    {
        $usuarioOperativoId = $data['usuario_operativo_id'];

        // Validar subrubro permitido para OPERATIVO
        $subrubro = Subrubro::findOrFail($data['subrubro_id']);
        if ($subrubro->permitido_para !== 'OPERATIVO') {
            throw new \Exception(
                'El subrubro seleccionado no está permitido para usuarios operativos.'
            );
        }

        // Abrir caja si no existe (incluye validación de caja vieja)
        $caja = $this->abrirCajaSiNoExiste($usuarioOperativoId);

        // Crear movimiento
        return MovimientoOperativo::create([
            'caja_operativa_id' => $caja->id,
            'fecha' => $data['fecha'] ?? Carbon::now()->toDateString(),
            'tipo_caja_id' => $data['tipo_caja_id'],
            'subrubro_id' => $data['subrubro_id'],
            'monto' => $data['monto'],
            'observaciones' => $data['observaciones'] ?? null,
            'usuario_id' => $usuarioOperativoId,
        ]);
    }

    /**
     * Cerrar una caja operativa
     *
     * @param int $cajaId
     * @param int $usuarioId
     * @param bool $esAdmin Si es admin cerrando caja de otro usuario
     * @return CajaOperativa
     * @throws \Exception
     */
    public function cerrarCajaOperativa(int $cajaId, int $usuarioId, bool $esAdmin = false): CajaOperativa
    {
        $caja = CajaOperativa::findOrFail($cajaId);

        if ($caja->estado !== 'ABIERTA') {
            throw new \Exception('La caja no está abierta.');
        }

        if (!$esAdmin && $caja->usuario_operativo_id !== $usuarioId) {
            throw new \Exception('No tenés permiso para cerrar esta caja.');
        }

        $caja->estado = 'CERRADA';
        $caja->cierre_at = Carbon::now();

        if ($esAdmin) {
            $caja->cerrada_por_admin = true;
            $caja->usuario_admin_cierre_id = $usuarioId;
        }

        $caja->save();

        return $caja;
    }

    /**
     * Validar una caja (solo ADMIN)
     *
     * Si la caja está ABIERTA, primero la cierra como admin.
     * Al validar, refleja los movimientos en el cashflow.
     * Todo dentro de una transacción para garantizar consistencia.
     *
     * @param int $cajaId
     * @param int $adminId
     * @return CajaOperativa
     * @throws \Exception
     */
    public function validarCaja(int $cajaId, int $adminId): CajaOperativa
    {
        return DB::transaction(function () use ($cajaId, $adminId) {
            $caja = CajaOperativa::findOrFail($cajaId);

            // Si está abierta, cerrarla primero como admin
            if ($caja->estado === 'ABIERTA') {
                $caja = $this->cerrarCajaOperativa($cajaId, $adminId, true);
            }

            // Si ya está VALIDADA, es idempotente (el cashflow service también lo es)
            if ($caja->estado === 'VALIDADA') {
                // Asegurar que el cashflow esté reflejado (idempotente)
                $this->getCashflowIntegracion()->reflejarCajaEnCashflow($cajaId, $adminId);
                return $caja;
            }

            if ($caja->estado !== 'CERRADA') {
                throw new \Exception('Solo se pueden validar cajas cerradas.');
            }

            // Marcar como validada
            $caja->estado = 'VALIDADA';
            $caja->usuario_admin_validacion_id = $adminId;
            $caja->validada_at = Carbon::now();
            $caja->save();

            // Reflejar movimientos en cashflow
            $this->getCashflowIntegracion()->reflejarCajaEnCashflow($cajaId, $adminId);

            return $caja;
        });
    }

    /**
     * Rechazar una caja (solo ADMIN)
     *
     * @param int $cajaId
     * @param int $adminId
     * @param string $motivo
     * @return CajaOperativa
     * @throws \Exception
     */
    public function rechazarCaja(int $cajaId, int $adminId, string $motivo): CajaOperativa
    {
        $caja = CajaOperativa::findOrFail($cajaId);

        if (!in_array($caja->estado, ['CERRADA', 'ABIERTA'])) {
            throw new \Exception('Solo se pueden rechazar cajas abiertas o cerradas.');
        }

        // Si está abierta, cerrarla primero
        if ($caja->estado === 'ABIERTA') {
            $caja->cierre_at = Carbon::now();
            $caja->cerrada_por_admin = true;
            $caja->usuario_admin_cierre_id = $adminId;
        }

        $caja->estado = 'RECHAZADA';
        $caja->motivo_rechazo = $motivo;
        $caja->usuario_admin_validacion_id = $adminId;
        $caja->save();

        return $caja;
    }

    /**
     * Obtener cajas pendientes de validación (CERRADAS)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerCajasPendientes()
    {
        return CajaOperativa::where('estado', 'CERRADA')
            ->with(['usuarioOperativo', 'movimientos'])
            ->orderBy('cierre_at', 'asc')
            ->get();
    }
}
