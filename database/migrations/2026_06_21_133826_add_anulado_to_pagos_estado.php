<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El enum original tiene: 'pagado','parcial','adeuda','COMPLETADO'
        // Se agrega 'ANULADO' para marcar pagos revertidos por cancelación de cobro
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda','COMPLETADO','ANULADO') NOT NULL DEFAULT 'pagado'"
        );
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE pagos MODIFY estado ENUM('pagado','parcial','adeuda','COMPLETADO') NOT NULL DEFAULT 'pagado'"
        );
    }
};
