<?php

namespace App\Observers;

use App\Models\{Deporte, DeudaCuota, Liquidacion, Pago, Profesor};
use App\Services\HistorialAnaliticoReportesService;
use Illuminate\Database\Eloquent\Model;

class HistorialAnaliticoReportesObserver
{
    public function created(Model $modelo): void
    {
        if (!$modelo instanceof Deporte) app(HistorialAnaliticoReportesService::class)->registrar($modelo);
    }

    public function updated(Model $modelo): void
    {
        if ($modelo instanceof Deporte) {
            if (!$modelo->wasChanged('tipo_liquidacion')) return;
            $modelo->profesores()->orderBy('id')->chunkById(200, function ($filas) {
                foreach ($filas as $fila) app(HistorialAnaliticoReportesService::class)->registrar($fila);
            });
            return;
        }
        $campos = match (true) {
            $modelo instanceof DeudaCuota => ['monto_original','monto_pagado','monto_condonado','estado','alumno_id','periodo'],
            $modelo instanceof Profesor => ['valor_hora','porcentaje_comision','deporte_id'],
            $modelo instanceof Pago => ['estado','fecha_pago','mes','anio','alumno_id'],
            $modelo instanceof Liquidacion => ['estado','estado_pago','total_calculado','monto_final','valor_hora_aplicado','porcentaje_comision_aplicado'],
        };
        if ($modelo->wasChanged($campos)) app(HistorialAnaliticoReportesService::class)->registrar($modelo);
    }

    public function deleted(Model $modelo): void
    {
        if (!$modelo instanceof Deporte) app(HistorialAnaliticoReportesService::class)->registrar($modelo, true);
    }
}
