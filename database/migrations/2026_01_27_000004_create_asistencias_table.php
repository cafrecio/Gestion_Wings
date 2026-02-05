<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de asistencias: registro de asistencia de alumnos a clases.
     * - Un alumno no puede asistir a dos clases que se solapen en fecha + horario.
     *   (Esta validación vive en backend Service, no en BD)
     */
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->onDelete('cascade');
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->boolean('presente');
            $table->timestamps();

            // Un alumno solo puede tener una asistencia por clase
            $table->unique(['clase_id', 'alumno_id'], 'asistencia_clase_alumno_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
