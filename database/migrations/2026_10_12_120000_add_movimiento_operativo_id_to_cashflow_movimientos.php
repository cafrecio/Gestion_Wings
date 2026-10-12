<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->foreignId('movimiento_operativo_id')
                ->nullable()
                ->after('referencia_id')
                ->constrained('movimientos_operativos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movimiento_operativo_id');
        });
    }
};
