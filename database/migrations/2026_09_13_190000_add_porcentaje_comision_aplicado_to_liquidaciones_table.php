<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIN-06: congelar el porcentaje de comisión aplicado al generar la liquidación.
     * Evita que cambios futuros en el porcentaje del profesor alteren liquidaciones pasadas.
     */
    public function up(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('porcentaje_comision_aplicado', 5, 2)
                ->nullable()
                ->after('tipo');
        });

        // Backfill de liquidaciones existentes de tipo COMISION con el porcentaje actual del profesor.
        // Es lo único posible para registros existentes: no reconstruye un histórico que no se guardó.
        $liquidacionesComision = DB::table('liquidaciones')
            ->where('tipo', 'COMISION')
            ->get(['id', 'profesor_id']);

        foreach ($liquidacionesComision as $liq) {
            $porcentaje = DB::table('profesores')
                ->where('id', $liq->profesor_id)
                ->value('porcentaje_comision');

            if ($porcentaje !== null) {
                DB::table('liquidaciones')
                    ->where('id', $liq->id)
                    ->update(['porcentaje_comision_aplicado' => $porcentaje]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn('porcentaje_comision_aplicado');
        });
    }
};