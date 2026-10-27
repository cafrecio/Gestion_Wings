<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excepciones_cobranza', function (Blueprint $table) {
            $table->dropUnique('excepcion_registro_tipo_unico');
            $table->index(['registro_tipo', 'registro_id']);
        });
    }

    public function down(): void
    {
        if (DB::table('excepciones_cobranza')->select('tipo', 'registro_tipo', 'registro_id')
            ->groupBy('tipo', 'registro_tipo', 'registro_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('No se puede restaurar la unicidad sin perder decisiones de asistencia.');
        }
        Schema::table('excepciones_cobranza', function (Blueprint $table) {
            $table->unique(['tipo', 'registro_tipo', 'registro_id'], 'excepcion_registro_tipo_unico');
            $table->dropIndex(['registro_tipo', 'registro_id']);
        });
    }
};
