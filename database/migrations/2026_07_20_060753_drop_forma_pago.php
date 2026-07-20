<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D4 — La "forma de pago" (efectivo/débito/crédito/transferencia) se quitó
 * del flujo de cobro por ser redundante con el tipo de caja. Quedaron
 * huérfanas la columna pagos.forma_pago_id (con su FK) y la tabla
 * formas_pago. Verificado: 0 pagos usan forma_pago_id. Se eliminan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['forma_pago_id']);
            $table->dropColumn('forma_pago_id');
        });

        Schema::dropIfExists('formas_pago');
    }

    public function down(): void
    {
        Schema::create('formas_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('forma_pago_id')
                ->nullable()
                ->after('monto_final')
                ->constrained('formas_pago')
                ->nullOnDelete();
        });
    }
};
