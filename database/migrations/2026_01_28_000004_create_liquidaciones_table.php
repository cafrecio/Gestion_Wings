<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')
                ->constrained('profesores')
                ->onDelete('restrict');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->enum('tipo', ['HORA', 'COMISION']);
            $table->decimal('total_calculado', 12, 2)->default(0);
            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->timestamps();

            $table->unique(['profesor_id', 'mes', 'anio']);
            $table->index(['mes', 'anio']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};
