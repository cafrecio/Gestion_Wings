<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // La FK acredita de quien es el sueldo; el nombre puede haberse renombrado.
        // No desreservar otros conceptos por una FK mal vinculada ni un sueldo docente
        // compartido por datos antiguos: estos siguen pasando solo por Liquidaciones.
        DB::table('subrubros')
            ->where('es_reservado_sistema', true)
            ->whereIn('id', DB::table('users')->where('rol', 'OPERATIVO')->whereNotNull('subrubro_id')->select('subrubro_id'))
            ->whereIn('rubro_id', DB::table('rubros')->where('nombre', 'Sueldos')->where('tipo', 'EGRESO')->select('id'))
            ->whereNotIn('id', DB::table('profesores')->whereNotNull('subrubro_id')->select('subrubro_id'))
            ->update(['es_reservado_sistema' => false]);
    }

    public function down(): void
    {
        // Correccion de datos sin una foto de las reservas anteriores. No se inventa
        // esa foto ni se vuelven a bloquear sueldos que pudieron usarse desde entonces.
    }
};
