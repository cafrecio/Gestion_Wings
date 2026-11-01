<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** T100: qué cobro hizo nacer una cuota adelantada. Las cuotas anteriores quedan sin dato. */
    public function up(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->foreignId('pago_origen_id')->nullable()->constrained('pagos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pago_origen_id');
        });
    }
};
