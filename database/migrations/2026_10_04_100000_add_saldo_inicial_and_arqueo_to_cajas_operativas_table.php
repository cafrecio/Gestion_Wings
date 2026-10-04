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
        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->decimal('saldo_inicial', 10, 2)->default(0)->after('estado');
            $table->decimal('saldo_cierre_efectivo', 10, 2)->nullable()->after('saldo_inicial');
            $table->decimal('diferencia_cierre', 10, 2)->nullable()->after('saldo_cierre_efectivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->dropColumn(['saldo_inicial', 'saldo_cierre_efectivo', 'diferencia_cierre']);
        });
    }
};
