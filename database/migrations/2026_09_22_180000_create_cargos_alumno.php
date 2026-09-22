<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inscripcion_personas', function (Blueprint $t) {
            $t->string('dni', 20)->primary();
        });
        Schema::create('cargos_alumno', function (Blueprint $t) {
            $t->id();
            $t->foreignId('alumno_id')->constrained('alumnos')->restrictOnDelete();
            $t->enum('tipo', ['INSCRIPCION', 'PUNITORIO']);
            $t->string('clave_origen', 100)->unique();
            $t->string('dni', 20)->nullable()->index();
            $t->foreignId('deuda_cuota_id')->nullable()->constrained('deuda_cuotas')->restrictOnDelete();
            $t->foreignId('subrubro_id')->constrained('subrubros')->restrictOnDelete();
            $t->decimal('monto_original', 10, 2);
            $t->decimal('monto_condonado', 10, 2)->default(0);
            $t->enum('estado', ['VIGENTE', 'ANULADO'])->default('VIGENTE');
            $t->json('calculo');
            $t->timestamps();
        });
        Schema::create('pago_cargo_alumno', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pago_id')->constrained('pagos')->restrictOnDelete();
            $t->foreignId('cargo_alumno_id')->constrained('cargos_alumno')->restrictOnDelete();
            $t->decimal('monto_aplicado', 10, 2);
            $t->timestamps();
            $t->unique(['pago_id', 'cargo_alumno_id']);
        });
        Schema::create('cargo_alumno_eventos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cargo_alumno_id')->constrained('cargos_alumno')->restrictOnDelete();
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('accion', 30);
            $t->text('motivo');
            $t->json('detalle')->nullable();
            $t->timestamp('created_at');
        });
        Schema::table('alumnos', function (Blueprint $t) {
            $t->uuid('alta_token')->nullable()->unique();
            $t->string('alta_fingerprint', 64)->nullable();
        });
        // NULL identifica pagos históricos: su monto_final sigue siendo íntegramente cuota.
        Schema::table('pagos', fn (Blueprint $t) => $t->decimal('monto_cuota', 10, 2)->nullable());
        foreach ([['inscripcion_importe', '5000.00', 'Importe de inscripción por única vez', 'string'], ['inscripcion_fecha_corte', '2026-09-23', 'Fecha fija de inicio de inscripción', 'string']] as [$clave, $valor, $descripcion, $tipo]) {
            DB::table('configuraciones')->insertOrIgnore(compact('clave', 'valor', 'descripcion', 'tipo'));
        }
        $rubro = DB::table('rubros')->where('nombre', 'Inscripciones')->first();
        $id = $rubro?->id ?? DB::table('rubros')->insertGetId(['nombre' => 'Inscripciones', 'tipo' => 'INGRESO', 'es_reservado_sistema' => true, 'created_at' => now(), 'updated_at' => now()]);
        if ($rubro && $rubro->tipo !== 'INGRESO') {
            throw new RuntimeException('El rubro Inscripciones existente no es INGRESO. Revisar antes de migrar.');
        }
        DB::table('rubros')->where('id', $id)->update(['es_reservado_sistema' => true]);
        if (!DB::table('subrubros')->where('nombre', 'Inscripción al club')->exists()) {
            DB::table('subrubros')->insert(['rubro_id' => $id, 'nombre' => 'Inscripción al club', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'es_reservado_sistema' => true, 'activo' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_alumno_eventos');
        Schema::dropIfExists('pago_cargo_alumno');
        Schema::dropIfExists('cargos_alumno');
        Schema::dropIfExists('inscripcion_personas');
        Schema::table('alumnos', fn (Blueprint $t) => $t->dropColumn(['alta_token', 'alta_fingerprint']));
        Schema::table('pagos', fn (Blueprint $t) => $t->dropColumn('monto_cuota'));
    }
};
