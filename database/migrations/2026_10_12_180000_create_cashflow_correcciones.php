<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->unsignedInteger('version_correccion')->default(0);
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('contra_origen_id')->nullable()->unique()
                ->constrained('cashflow_movimientos')->restrictOnDelete();
        });
        Schema::create('cashflow_correcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimiento_id')->constrained('cashflow_movimientos')->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('accion', 12);
            $table->uuid('clave')->unique();
            $table->string('huella', 64);
            $table->unsignedInteger('version');
            $table->json('antes');
            $table->json('despues');
            $table->string('motivo', 500)->nullable();
            $table->timestamps();
            $table->unique(['movimiento_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_correcciones');
        Schema::table('cashflow_movimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contra_origen_id');
            $table->dropColumn(['version_correccion', 'anulado_at']);
        });
    }
};
