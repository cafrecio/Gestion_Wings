<?php

use App\Models\CashflowMovimiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * D2 — Normaliza el valor legacy 'MOVIMIENTO_OPERATIVO' (que escribía el
 * DemoSeeder) al canónico REF_CAJA ('CAJA_OPERATIVA') que usa el servicio
 * real. Ambos representan lo mismo: el reflejo de una caja en cashflow.
 * Tenerlos con dos nombres causó movimientos duplicados en el historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('cashflow_movimientos')
            ->where('referencia_tipo', 'MOVIMIENTO_OPERATIVO')
            ->update(['referencia_tipo' => CashflowMovimiento::REF_CAJA]);
    }

    public function down(): void
    {
        // No se puede distinguir con certeza cuáles eran 'MOVIMIENTO_OPERATIVO'
        // originalmente; se deja sin revertir (el valor canónico es correcto).
    }
};
