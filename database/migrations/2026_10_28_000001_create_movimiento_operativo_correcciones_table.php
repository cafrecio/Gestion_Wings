<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('movimiento_operativo_correcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_operativa_id')->constrained('cajas_operativas')->restrictOnDelete();
            // Deliberadamente sin FK: el rastro sobrevive a la eliminación del movimiento.
            $table->unsignedBigInteger('movimiento_id');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('autor');
            $table->string('accion', 10);
            $table->json('antes');
            $table->json('despues')->nullable();
            $table->timestamp('created_at');
            $table->index(['caja_operativa_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_operativo_correcciones');
    }
};
