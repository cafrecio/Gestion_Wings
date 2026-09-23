<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            // NULL conserva el tratamiento previo de las deudas históricas.
            $table->decimal('porcentaje_alta', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('deuda_cuotas', fn (Blueprint $table) => $table->dropColumn('porcentaje_alta'));
    }
};
