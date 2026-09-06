<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El usuario OPERATIVO cobra sueldo igual que el profesor, pero no tenía
 * dónde imputarlo: había que crearle el subrubro a mano cada vez. Ahora el
 * alta se lo crea bajo "Sueldos" y el vínculo se guarda por FK, no
 * reconstruyendo el nombre — la misma lección de D3 en profesores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subrubro_id')
                ->nullable()
                ->after('profesor_id')
                ->constrained('subrubros')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['subrubro_id']);
            $table->dropColumn('subrubro_id');
        });
    }
};
