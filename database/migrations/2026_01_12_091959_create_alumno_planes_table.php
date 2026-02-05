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
        Schema::create('alumno_planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('grupo_planes')->onDelete('restrict');
            $table->date('fecha_desde');
            $table->date('fecha_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Solo un plan activo por alumno
            $table->unique(['alumno_id', 'activo'], 'unique_alumno_plan_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumno_planes');
    }
};
