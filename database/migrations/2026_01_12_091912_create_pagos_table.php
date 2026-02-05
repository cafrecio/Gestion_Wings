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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('grupo_planes')->onDelete('restrict');
            $table->foreignId('regla_primer_pago_id')->nullable()->constrained('reglas_primer_pago')->onDelete('set null');
            $table->unsignedTinyInteger('mes');
            $table->unsignedSmallInteger('anio');
            $table->decimal('monto_base', 10, 2);
            $table->decimal('porcentaje_aplicado', 5, 2);
            $table->decimal('monto_final', 10, 2);
            $table->foreignId('forma_pago_id')->constrained('formas_pago');
            $table->enum('estado', ['pagado', 'parcial', 'adeuda'])->default('pagado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
