<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidaciones', fn (Blueprint $table) => $table->decimal('monto_final', 12, 2)->nullable());
        Schema::create('liquidacion_ajustes', function (Blueprint $table) {
            $table->id();
            // Sin CASCADE: borrar una abierta conserva la evidencia del ajuste.
            $table->unsignedBigInteger('liquidacion_id')->index();
            $table->unsignedBigInteger('admin_id');
            $table->decimal('calculado', 12, 2);
            $table->decimal('anterior', 12, 2);
            $table->decimal('nuevo', 12, 2);
            $table->string('motivo', 255);
            $table->dateTime('registrado_en', 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidacion_ajustes');
        Schema::table('liquidaciones', fn (Blueprint $table) => $table->dropColumn('monto_final'));
    }
};
