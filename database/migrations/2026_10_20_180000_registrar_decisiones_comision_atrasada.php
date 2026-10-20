<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidaciones', fn (Blueprint $t) =>
            $t->decimal('total_atrasadas_excluidas', 12, 2)->default(0));
        Schema::table('liquidacion_detalles', function (Blueprint $t) {
            $t->foreignId('pago_id')->nullable()->constrained('pagos')->restrictOnDelete();
            $t->foreignId('deuda_cuota_id')->nullable()->constrained('deuda_cuotas')->restrictOnDelete();
            $t->string('periodo_cuota', 7)->nullable();
            $t->date('fecha_cobro')->nullable();
            $t->decimal('base_cuota', 12, 2)->nullable();
            $t->string('origen_cuota', 20)->nullable();
            $t->string('decision_atrasada', 20)->nullable();
            $t->unsignedBigInteger('decision_por')->nullable();
            $t->dateTime('decision_at', 6)->nullable();
        });
        Schema::create('liquidacion_decisiones_atrasadas', function (Blueprint $t) {
            $t->id();
            // Como liquidacion_ajustes: borrar una abierta no borra la evidencia.
            $t->unsignedBigInteger('liquidacion_id')->index();
            $t->unsignedBigInteger('detalle_id');
            $t->unsignedBigInteger('profesor_id');
            $t->string('periodo_liquidacion', 7);
            $t->unsignedBigInteger('pago_id');
            $t->unsignedBigInteger('deuda_cuota_id')->nullable();
            $t->string('periodo_cuota', 7);
            $t->decimal('base_cuota', 12, 2);
            $t->decimal('comision', 12, 2);
            $t->string('anterior', 20);
            $t->string('nueva', 20);
            $t->unsignedBigInteger('admin_id');
            $t->dateTime('registrado_en', 6);
        });
        // Sin backfill: los documentos históricos conservan importes y origen desconocido.
    }

    public function down(): void
    {
        // No borrar decisiones financieras ni su procedencia al retroceder código.
    }
};
