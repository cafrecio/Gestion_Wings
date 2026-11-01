<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cashflow_transferencias', function (Blueprint $table) {
            $table->timestamp('anulada_at')->nullable();
            $table->foreignId('anulo_admin_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('motivo_anulacion', 500)->nullable();
        });
    }

    public function down(): void
    {
        // Como la cabecera T61, esta auditoría financiera no se borra con un rollback.
    }
};
