<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D5 — deuda_cuotas tenía dos índices sobre (alumno_id, periodo): el
 * UNIQUE 'deuda_cuotas_alumno_periodo_unique' y un index redundante
 * 'deuda_cuotas_alumno_id_periodo_index'. El UNIQUE ya indexa esas
 * columnas; el segundo solo ocupa espacio y ralentiza escrituras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->dropIndex('deuda_cuotas_alumno_id_periodo_index');
        });
    }

    public function down(): void
    {
        Schema::table('deuda_cuotas', function (Blueprint $table) {
            $table->index(['alumno_id', 'periodo'], 'deuda_cuotas_alumno_id_periodo_index');
        });
    }
};
