<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            // T38: día en que se reactivó al alumno, solo en la cuota que se generó ese día.
            // Le da el mismo plazo que una cuota de alta. NULL en todas las demás.
            $table->date('fecha_reingreso')->nullable()->after('porcentaje_alta');
        });
    }

    public function down(): void
    {
        Schema::table('deuda_cuotas', fn (Blueprint $table) => $table->dropColumn('fecha_reingreso'));
    }
};
