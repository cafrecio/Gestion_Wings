<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos para el pago de liquidaciones.
     * Permiten registrar cuándo y cómo se pagó a un profesor.
     */
    public function up(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->enum('estado_pago', ['PENDIENTE', 'PAGADA'])->default('PENDIENTE')->after('estado');
            $table->datetime('pagada_at')->nullable()->after('estado_pago');
            $table->foreignId('pagada_por_admin_id')->nullable()->constrained('users')->after('pagada_at');
            $table->date('pagada_fecha')->nullable()->after('pagada_por_admin_id');
            $table->foreignId('pagada_tipo_caja_id')->nullable()->constrained('tipos_caja')->after('pagada_fecha');
            $table->foreignId('pagada_subrubro_id')->nullable()->constrained('subrubros')->after('pagada_tipo_caja_id');
        });
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropForeign(['pagada_por_admin_id']);
            $table->dropForeign(['pagada_tipo_caja_id']);
            $table->dropForeign(['pagada_subrubro_id']);
            $table->dropColumn([
                'estado_pago',
                'pagada_at',
                'pagada_por_admin_id',
                'pagada_fecha',
                'pagada_tipo_caja_id',
                'pagada_subrubro_id',
            ]);
        });
    }
};
