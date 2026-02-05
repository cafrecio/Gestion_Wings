<?php

namespace App\Services;

use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use Carbon\Carbon;

class CashflowService
{
    /**
     * Registrar un movimiento de cashflow (solo ADMIN)
     *
     * Valida que el subrubro permita ADMIN.
     * Guarda directo, sin caja operativa.
     *
     * @param array $data [usuario_admin_id, subrubro_id, tipo_caja_id, monto, fecha?, observaciones?, referencia_tipo?, referencia_id?]
     * @return CashflowMovimiento
     * @throws \Exception
     */
    public function registrarMovimientoAdmin(array $data): CashflowMovimiento
    {
        // Validar subrubro permitido para ADMIN
        $subrubro = Subrubro::findOrFail($data['subrubro_id']);
        if ($subrubro->permitido_para !== 'ADMIN') {
            throw new \Exception(
                'El subrubro seleccionado no está permitido para administradores.'
            );
        }

        return CashflowMovimiento::create([
            'fecha' => $data['fecha'] ?? Carbon::now()->toDateString(),
            'subrubro_id' => $data['subrubro_id'],
            'tipo_caja_id' => $data['tipo_caja_id'],
            'monto' => $data['monto'],
            'observaciones' => $data['observaciones'] ?? null,
            'usuario_admin_id' => $data['usuario_admin_id'],
            'referencia_tipo' => $data['referencia_tipo'] ?? null,
            'referencia_id' => $data['referencia_id'] ?? null,
        ]);
    }
}
