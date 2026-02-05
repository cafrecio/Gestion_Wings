<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla pivot clase_profesor: relaciona clases con profesores.
     * - Una clase puede tener uno o más profesores.
     * - Un profesor NO puede estar asignado a dos clases que se solapen en fecha + horario.
     *   (Esta validación vive en backend Service, no en BD)
     */
    public function up(): void
    {
        Schema::create('clase_profesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->onDelete('cascade');
            $table->foreignId('profesor_id')->constrained('profesores')->onDelete('cascade');
            $table->timestamps();

            // Un profesor solo puede estar asignado una vez a la misma clase
            $table->unique(['clase_id', 'profesor_id'], 'clase_profesor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clase_profesor');
    }
};
