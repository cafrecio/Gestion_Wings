<?php

namespace Tests\Concerns;

use App\Models\CajaOperativa;
use App\Models\User;
use App\Services\CajaService;

trait VerificaMovimientosDeCaja
{
    protected function verificarMovimientosDeCaja(CajaOperativa $caja, User $admin): void
    {
        // Las pruebas anteriores de Cashflow/Reportes también deben realizar la revisión T36.
        // No altera casos de idempotencia ni sustituye las aserciones del escenario.
        if ($caja->fresh()->estado === CajaOperativa::ESTADO_VALIDADA) {
            return;
        }
        foreach ($caja->movimientos()->orderBy('id')->get() as $movimiento) {
            app(CajaService::class)->verificarMovimiento(
                $caja->id, $movimiento->id, $admin->id, true, $movimiento->huellaVerificacion()
            );
        }
    }
}
