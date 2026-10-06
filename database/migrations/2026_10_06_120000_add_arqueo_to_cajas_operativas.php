<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_mostrador', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->foreignId('tipo_caja_id')->nullable()->constrained('tipos_caja')->restrictOnDelete();
            $table->foreignId('configurado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('configurado_at')->nullable();
        });
        // Mutex persistente del cajón compartido. No es un ingreso ni un catálogo.
        DB::table('caja_mostrador')->insert(['id' => 1]);

        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->foreignId('tipo_caja_efectivo_id')->nullable()->constrained('tipos_caja')->restrictOnDelete();
            $table->foreignId('caja_origen_id')->nullable()->constrained('cajas_operativas')->restrictOnDelete();
            // NULL significa dato histórico no declarado; no inventar un cero contado.
            $table->decimal('efectivo_heredado', 12, 2)->nullable();
            $table->decimal('efectivo_inicial', 12, 2)->nullable();
            $table->text('motivo_apertura')->nullable();
            $table->decimal('efectivo_esperado', 12, 2)->nullable();
            $table->decimal('efectivo_contado', 12, 2)->nullable();
            $table->decimal('diferencia_efectivo', 12, 2)->nullable();
            $table->decimal('cambio_retenido', 12, 2)->nullable();
            $table->decimal('efectivo_retirado', 12, 2)->nullable();
            $table->foreignId('usuario_cierre_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_apertura_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cajas_operativas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('usuario_cierre_id');
            $table->dropConstrainedForeignId('usuario_apertura_id');
            $table->dropConstrainedForeignId('caja_origen_id');
            $table->dropConstrainedForeignId('tipo_caja_efectivo_id');
            $table->dropColumn([
                'efectivo_heredado', 'efectivo_inicial', 'motivo_apertura',
                'efectivo_esperado', 'efectivo_contado', 'diferencia_efectivo',
                'cambio_retenido', 'efectivo_retirado',
            ]);
        });
        Schema::dropIfExists('caja_mostrador');
    }
};
