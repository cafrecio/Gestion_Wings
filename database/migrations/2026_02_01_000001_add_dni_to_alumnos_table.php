<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campo DNI a alumnos con constraint único compuesto (dni, deporte_id).
     * Esto permite que la misma persona (mismo DNI) exista en deportes distintos.
     */
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->string('dni')->nullable()->after('apellido');

            // Constraint único compuesto: mismo DNI puede existir en deportes distintos
            $table->unique(['dni', 'deporte_id'], 'alumnos_dni_deporte_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropUnique('alumnos_dni_deporte_unique');
            $table->dropColumn('dni');
        });
    }
};
