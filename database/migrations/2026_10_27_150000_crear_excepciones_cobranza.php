<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excepciones_cobranza', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->string('motivo', 500);
            $table->foreignId('autor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('alumno_id')->constrained('alumnos')->restrictOnDelete();
            $table->string('registro_tipo', 20);
            $table->unsignedBigInteger('registro_id');
            $table->dateTime('ocurrida_at')->index();
            $table->json('contexto');
            $table->timestamps();
            $table->unique(['tipo', 'registro_tipo', 'registro_id'], 'excepcion_registro_tipo_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excepciones_cobranza');
    }
};
