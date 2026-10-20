<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T52 (DU-09, Carlos 20/10: «Los cambios son siempre para adelante»): desde qué día
 * está el alumno en su grupo actual. Quien entró después de una clase no aparece en
 * ella.
 *
 * El plan no sirve para esto: con el mes pagado, el cambio de grupo es inmediato y el
 * plan nuevo empieza el mes siguiente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->date('grupo_desde')->nullable()->after('grupo_id');
        });

        // Vacío = nunca cambió de grupo: vale su fecha de ingreso, aunque sea futura (T50) o se
        // corrija después. Solo se completa para quien muestra un cambio: su primer plan del
        // grupo actual empieza después de su ingreso. Esa fecha es una aproximación (con el mes
        // pagado el plan nuevo se difiere al mes siguiente) y nunca queda en el futuro.
        DB::table('alumnos')->orderBy('id')->chunkById(500, function ($alumnos) {
            foreach ($alumnos as $alumno) {
                $primerPlan = DB::table('alumno_planes')
                    ->join('grupo_planes', 'grupo_planes.id', '=', 'alumno_planes.plan_id')
                    ->where('alumno_planes.alumno_id', $alumno->id)
                    ->where('grupo_planes.grupo_id', $alumno->grupo_id)
                    ->min('alumno_planes.fecha_desde');

                if ($primerPlan && $alumno->fecha_alta && $primerPlan > $alumno->fecha_alta) {
                    DB::table('alumnos')->where('id', $alumno->id)
                        ->update(['grupo_desde' => min($primerPlan, now()->toDateString())]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            $table->dropColumn('grupo_desde');
        });
    }
};
