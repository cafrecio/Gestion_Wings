<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTA: Esta migración asume que la tabla cashflow_movimientos está vacía.
     * Si hubiera filas existentes, fallaría por la constraint NOT NULL sin default.
     * En ese caso, habría que:
     *   1. Agregar columna nullable
     *   2. UPDATE cashflow_movimientos SET tipo_caja_id = X (un tipo_caja válido)
     *   3. ALTER para hacerla NOT NULL
     */
    public function up(): void
    {
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->foreignId('tipo_caja_id')
                ->after('subrubro_id')
                ->constrained('tipos_caja')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->dropForeign(['tipo_caja_id']);
            $table->dropColumn('tipo_caja_id');
        });
    }
};
