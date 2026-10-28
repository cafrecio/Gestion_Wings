<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deporte_liquidacion_vigencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deporte_id')->constrained('deportes')->restrictOnDelete();
            $table->string('tipo', 10);
            $table->date('vigente_desde');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['deporte_id', 'vigente_desde', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deporte_liquidacion_vigencias');
    }
};
