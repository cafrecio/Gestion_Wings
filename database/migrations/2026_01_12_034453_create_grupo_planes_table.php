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
        Schema::create('grupo_planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->integer('clases_por_semana')->unsigned();
            $table->decimal('precio_mensual', 10, 2)->unsigned();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índice único para evitar duplicados de clases_por_semana por grupo
            $table->unique(['grupo_id', 'clases_por_semana', 'activo'], 'unique_grupo_clases_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupo_planes');
    }
};
