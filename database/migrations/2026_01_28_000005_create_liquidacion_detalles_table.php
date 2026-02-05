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
        Schema::create('liquidacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')
                ->constrained('liquidaciones')
                ->onDelete('cascade');
            $table->string('tipo_referencia', 20);
            $table->unsignedBigInteger('referencia_id');
            $table->decimal('monto', 10, 2);
            $table->string('descripcion')->nullable();
            $table->timestamps();

            $table->index(['liquidacion_id']);
            $table->index(['tipo_referencia', 'referencia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_detalles');
    }
};
