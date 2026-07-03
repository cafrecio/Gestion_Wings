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
        Schema::table('alumnos_revision_cobranza', function (Blueprint $table) {
            $table->enum('resolucion', ['CONTINUA', 'INACTIVO', 'REACTIVADO_AUTO'])
                ->nullable()
                ->after('estado_revision');
            $table->text('nota_resolucion')->nullable()->after('resolucion');
            $table->foreignId('usuario_resolucion_id')
                ->nullable()
                ->after('nota_resolucion')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resuelto_at')->nullable()->after('usuario_resolucion_id');
        });
    }

    public function down(): void
    {
        Schema::table('alumnos_revision_cobranza', function (Blueprint $table) {
            $table->dropForeign(['usuario_resolucion_id']);
            $table->dropColumn(['resolucion', 'nota_resolucion', 'usuario_resolucion_id', 'resuelto_at']);
        });
    }
};
