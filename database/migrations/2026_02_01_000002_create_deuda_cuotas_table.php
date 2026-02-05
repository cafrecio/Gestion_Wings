<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabla de deudas de cuotas mensuales.
     * La deuda pertenece implícitamente a un deporte vía el alumno.
     */
    public function up(): void
    {
        Schema::create('deuda_cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->string('periodo', 7); // Formato YYYY-MM
            $table->decimal('monto_original', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->enum('estado', ['PENDIENTE', 'PAGADA', 'CONDONADA', 'AJUSTADA'])->default('PENDIENTE');
            $table->timestamps();

            // Índice para búsquedas por alumno y período
            $table->index(['alumno_id', 'periodo']);

            // Constraint único: un alumno solo puede tener una deuda por período
            $table->unique(['alumno_id', 'periodo'], 'deuda_cuotas_alumno_periodo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deuda_cuotas');
    }
};
