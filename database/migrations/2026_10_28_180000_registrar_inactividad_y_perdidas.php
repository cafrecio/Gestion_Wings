<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', fn (Blueprint $t) => $t->timestamp('inactivo_desde')->nullable());
        Schema::create('alumno_estado_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('alumno_id')->nullable()->constrained('alumnos')->nullOnDelete();
            $t->unsignedBigInteger('alumno_referencia');
            $t->unsignedBigInteger('deporte_id')->nullable();
            $t->boolean('activo');
            $t->timestamp('fecha')->index();
        });
        foreach (['deuda_cuotas', 'cargos_alumno'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->decimal('monto_perdido', 10, 2)->default(0));
        }
        Schema::create('deudas_perdidas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('alumno_id')->constrained('alumnos')->restrictOnDelete();
            $t->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $t->decimal('importe', 12, 2);
            $t->json('detalle');
            $t->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deudas_perdidas');
        foreach (['deuda_cuotas', 'cargos_alumno'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropColumn('monto_perdido'));
        }
        Schema::dropIfExists('alumno_estado_historial');
        Schema::table('alumnos', fn (Blueprint $t) => $t->dropColumn('inactivo_desde'));
    }
};
