<?php

use App\Support\ClasificacionSubrubros;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        ClasificacionSubrubros::reservarTraspasos();
        Schema::create('cashflow_transferencias', function (Blueprint $table) {
            $table->id();
            $table->uuid('clave')->unique();
            $table->foreignId('salida_id')->constrained('tipos_caja');
            $table->foreignId('destino_id')->constrained('tipos_caja');
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('observaciones', 500)->nullable();
            $table->foreignId('admin_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // No borrar el vínculo de movimientos financieros existentes ni desproteger catálogo.
    }
};
