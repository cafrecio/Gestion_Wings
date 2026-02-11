<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencia_excesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained('asistencias')->onDelete('cascade');
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->date('fecha_clase');
            $table->enum('motivo', ['EXTRA', 'RECUPERA']);
            $table->text('detalle')->nullable();
            $table->timestamps();

            $table->unique('asistencia_id');
            $table->index('alumno_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_excesos');
    }
};
