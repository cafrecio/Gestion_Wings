<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla pivote para relacionar pagos con deudas de cuota.
     * Un pago puede aplicarse a múltiples períodos.
     */
    public function up(): void
    {
        Schema::create('pago_deuda_cuota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->onDelete('cascade');
            $table->foreignId('deuda_cuota_id')->constrained('deuda_cuotas')->onDelete('cascade');
            $table->decimal('monto_aplicado', 12, 2);
            $table->timestamps();

            // Un pago solo puede aplicarse una vez a cada deuda
            $table->unique(['pago_id', 'deuda_cuota_id'], 'pago_deuda_cuota_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_deuda_cuota');
    }
};
