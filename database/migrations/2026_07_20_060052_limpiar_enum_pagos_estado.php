<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * D6 — Las columnas de estado ya eran ENUM (dominio cerrado en BD), salvo
 * pagos.estado que arrastraba valores legacy del flujo viejo
 * ('pagado','parcial','adeuda') y tenía default 'pagado'. El flujo actual
 * solo usa 'COMPLETADO' y 'ANULADO'. Se limpia el enum y el default.
 * (Verificado: los 145 pagos son COMPLETADO; ninguno usa los valores viejos.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE pagos MODIFY COLUMN estado ENUM('COMPLETADO','ANULADO') NOT NULL DEFAULT 'COMPLETADO'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE pagos MODIFY COLUMN estado ENUM('pagado','parcial','adeuda','COMPLETADO','ANULADO') NOT NULL DEFAULT 'pagado'"
            );
        }
    }
};
