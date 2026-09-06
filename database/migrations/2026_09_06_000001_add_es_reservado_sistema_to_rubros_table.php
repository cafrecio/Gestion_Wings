<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El alta de profesores y el alta de usuarios operativos buscan el rubro
 * "Sueldos" por nombre exacto. Renombrarlo desde la pantalla de rubros
 * dejaba de encontrarlo en silencio, y cambiarle el tipo de EGRESO a
 * INGRESO daba vuelta el signo de los sueldos en el cashflow.
 *
 * Los subrubros ya tenían este blindaje desde 2026_02_02_000003. Esta
 * migración lo replica un nivel arriba y marca los rubros que el sistema
 * necesita encontrar tal cual están: "Sueldos" (buscado por nombre) y
 * "Cuotas" (padre del subrubro reservado "Cuota Mensual").
 */
return new class extends Migration
{
    private const RUBROS_RESERVADOS = ['Sueldos', 'Cuotas'];

    public function up(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->boolean('es_reservado_sistema')->default(false)->after('observacion');
        });

        DB::table('rubros')
            ->whereIn('nombre', self::RUBROS_RESERVADOS)
            ->update(['es_reservado_sistema' => true]);
    }

    public function down(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->dropColumn('es_reservado_sistema');
        });
    }
};
