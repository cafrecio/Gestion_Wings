<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El enum original tiene: 'pagado','parcial','adeuda','COMPLETADO'
        // Se agrega 'ANULADO' para marcar pagos revertidos por cancelación de cobro
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda','COMPLETADO','ANULADO') NOT NULL DEFAULT 'pagado'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda','COMPLETADO') NOT NULL DEFAULT 'pagado'"
            );
        }
    }
};
