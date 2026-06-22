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
        Schema::table('movimientos_operativos', function (Blueprint $table) {
            $table->unsignedBigInteger('pago_id')->nullable()->after('alumno_id');
            $table->enum('estado', ['ACTIVO', 'CANCELADO'])->default('ACTIVO')->after('pago_id');
            $table->text('motivo_cancelacion')->nullable()->after('estado');

            $table->foreign('pago_id')->references('id')->on('pagos')->nullOnDelete();
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_operativos', function (Blueprint $table) {
            $table->dropForeign(['pago_id']);
            $table->dropIndex(['movimientos_operativos_estado_index']);
            $table->dropColumn(['pago_id', 'estado', 'motivo_cancelacion']);
        });
    }
};
