<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campo observaciones para trazabilidad de condonaciones/ajustes.
     */
    public function up(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
