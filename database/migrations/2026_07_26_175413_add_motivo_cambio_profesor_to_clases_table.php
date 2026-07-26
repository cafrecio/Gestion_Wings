<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Regla de negocio (Clases+Asistencias, cuestionario I3.0): reasignar
 * profesores de una clase con fecha ya pasada requiere motivo obligatorio
 * y queda restringido a ADMIN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->string('motivo_cambio_profesor', 255)->nullable()->after('motivo_cancelacion');
        });
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropColumn('motivo_cambio_profesor');
        });
    }
};
