<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D3 — La liquidación resolvía el subrubro del profesor reconstruyendo su
 * nombre por concatenación ("Deporte-Nombre Apellido") y buscándolo por
 * string exacto, con un fallback peligroso a "cualquier subrubro de
 * Sueldos" que podía imputar el sueldo al cajón de otro profesor.
 * Solución: FK real profesores.subrubro_id. Esta migración además ata los
 * profesores existentes a su subrubro actual, una única vez, por el mismo
 * criterio de nombre que se usaba hasta ahora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->foreignId('subrubro_id')
                ->nullable()
                ->after('porcentaje_comision')
                ->constrained('subrubros')
                ->nullOnDelete();
        });

        // Atar cada profesor existente a su subrubro de sueldos por el nombre
        // legacy, una sola vez. De acá en adelante el vínculo es por FK.
        $rubroSueldos = DB::table('rubros')->where('nombre', 'Sueldos')->first();
        if (!$rubroSueldos) {
            return;
        }

        $profesores = DB::table('profesores')
            ->leftJoin('deportes', 'profesores.deporte_id', '=', 'deportes.id')
            ->select('profesores.id', 'profesores.nombre', 'profesores.apellido', 'deportes.nombre as deporte_nombre')
            ->get();

        foreach ($profesores as $prof) {
            // Se prueban los dos formatos de nombre que existieron: el
            // canónico del código ("Deporte-Nombre Apellido") y el que dejó
            // el DemoSeeder ("Sueldo - Apellido, Nombre"). Es un match único
            // de datos legacy; de acá en más el vínculo es por FK.
            $candidatos = [
                ($prof->deporte_nombre ?? 'Sin deporte') . '-' . $prof->nombre . ' ' . $prof->apellido,
                'Sueldo - ' . $prof->apellido . ', ' . $prof->nombre,
            ];

            $subrubro = DB::table('subrubros')
                ->where('rubro_id', $rubroSueldos->id)
                ->whereIn('nombre', $candidatos)
                ->first();

            if ($subrubro) {
                DB::table('profesores')
                    ->where('id', $prof->id)
                    ->update(['subrubro_id' => $subrubro->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropForeign(['subrubro_id']);
            $table->dropColumn('subrubro_id');
        });
    }
};
