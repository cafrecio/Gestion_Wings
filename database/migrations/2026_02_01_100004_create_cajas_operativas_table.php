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
        Schema::create('cajas_operativas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_operativo_id')->constrained('users')->onDelete('cascade');
            $table->datetime('apertura_at');
            $table->datetime('cierre_at')->nullable();
            $table->enum('estado', ['ABIERTA', 'CERRADA', 'VALIDADA', 'RECHAZADA'])->default('ABIERTA');
            $table->boolean('cerrada_por_admin')->default(false);
            $table->foreignId('usuario_admin_cierre_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('usuario_admin_validacion_id')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('validada_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cajas_operativas');
    }
};
