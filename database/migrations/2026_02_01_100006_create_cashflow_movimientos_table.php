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
        Schema::create('cashflow_movimientos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('subrubro_id')->constrained('subrubros')->onDelete('cascade');
            $table->decimal('monto', 12, 2);
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_admin_id')->constrained('users')->onDelete('cascade');
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->timestamps();

            $table->index(['referencia_tipo', 'referencia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashflow_movimientos');
    }
};
