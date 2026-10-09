<?php

namespace App\Observers;

use App\Models\{CashflowMovimiento, MovimientoOperativo, Pago, Liquidacion};
use Illuminate\Database\Eloquent\Model;

class MovimientoReporteObserver
{
    public function saving(Model $movimiento): void
    {
        if (\App\Support\RegistroReporteDisponible::existe()) return;
        foreach (['reporte_tipo', 'reporte_clasificacion', 'reporte_deporte_id'] as $campo) unset($movimiento->$campo);
    }

    public function updating(Model $movimiento): void
    {
        if (!\App\Support\RegistroReporteDisponible::existe()) return;
        if (!$movimiento->isDirty('subrubro_id')) return;
        $movimiento->unsetRelation('subrubro');
        $movimiento->reporte_clasificacion = $movimiento->subrubro?->clasificacion_resultado;
        $movimiento->reporte_tipo = $movimiento->subrubro?->rubro?->tipo;
    }

    public function creating(Model $movimiento): void
    {
        if (!\App\Support\RegistroReporteDisponible::existe()) return;
        if (!array_key_exists('reporte_tipo', $movimiento->getAttributes())) {
            $movimiento->reporte_tipo = $movimiento->subrubro?->rubro?->tipo;
        }
        if (!array_key_exists('reporte_clasificacion', $movimiento->getAttributes())) {
            $movimiento->reporte_clasificacion = $movimiento->subrubro?->clasificacion_resultado;
        }
        if (array_key_exists('reporte_deporte_id', $movimiento->getAttributes())) return;
        if ($movimiento instanceof MovimientoOperativo) {
            $movimiento->reporte_deporte_id = $movimiento->alumno?->deporte_id;
        } elseif ($movimiento->referencia_tipo === CashflowMovimiento::REF_PAGO_CUOTA) {
            $movimiento->reporte_deporte_id = Pago::find($movimiento->referencia_id)?->alumno?->deporte_id;
        } elseif ($movimiento->referencia_tipo === CashflowMovimiento::REF_LIQUIDACION) {
            $origen = \Illuminate\Support\Facades\DB::table('reporte_eventos')->where('tipo', 'LIQUIDACION')
                ->where('origen_id', $movimiento->referencia_id)->orderBy('id')->first();
            $movimiento->reporte_deporte_id = $origen ? $origen->deporte_id : null;
        }
    }
}
