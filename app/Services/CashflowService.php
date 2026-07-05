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
     * El admin puede usar cualquier subrubro: los ADMIN y los de uso
     * compartido (permitido_para=OPERATIVO significa "ambos"). Solo se
     * bloquean los reservados del sistema (ej. Cuota Mensual), que deben
     * pasar por su flujo propio para mantener deudas consistentes.
     *
     * @param array $data [usuario_admin_id, subrubro_id, tipo_caja_id, monto, fecha?, observaciones?, referencia_tipo?, referencia_id?]
     * @return CashflowMovimiento
     * @throws \Exception
     */
    public function registrarMovimientoAdmin(array $data): CashflowMovimiento
    {
        $subrubro = Subrubro::findOrFail($data['subrubro_id']);
        if ($subrubro->es_reservado_sistema && empty($data['referencia_tipo'])) {
            throw new \Exception(
                'Este subrubro lo administra el sistema. Usá el flujo correspondiente (ej. cobro de cuotas).'
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
