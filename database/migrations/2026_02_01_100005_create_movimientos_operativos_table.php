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
        Schema::create('movimientos_operativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_operativa_id')->constrained('cajas_operativas')->onDelete('cascade');
            $table->date('fecha')->nullable();
            $table->foreignId('tipo_caja_id')->constrained('tipos_caja')->onDelete('cascade');
            $table->foreignId('subrubro_id')->constrained('subrubros')->onDelete('cascade');
            $table->decimal('monto', 12, 2);
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_operativos');
    }
};
