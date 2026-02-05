<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de clases: representa una clase concreta en una fecha y horario.
     * Un mismo grupo puede tener más de una clase en el mismo día y horario.
     * No se valida solapamiento por grupo.
     */
    public function up(): void
    {
        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin'); // Default: hora_inicio + 1 hora, editable por usuario
            $table->timestamps();

            // Índice para búsquedas por fecha
            $table->index(['fecha', 'hora_inicio', 'hora_fin'], 'clases_fecha_horario_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
