<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos_revision_cobranza', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->string('periodo_objetivo', 7); // YYYY-MM
            $table->string('motivo');
            $table->enum('estado_revision', ['PENDIENTE', 'RESUELTO'])->default('PENDIENTE');
            $table->timestamps();

            $table->unique(['alumno_id', 'periodo_objetivo'], 'revision_alumno_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos_revision_cobranza');
    }
};
