<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regla de negocio (Clases+Asistencias, cuestionario I3.0): corregir la
 * asistencia de una clase con fecha ya pasada requiere motivo obligatorio
 * (ej. "se nos paso cargar a Josefa", "se marco por error").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->string('motivo_correccion', 255)->nullable()->after('presente');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('motivo_correccion');
        });
    }
};
