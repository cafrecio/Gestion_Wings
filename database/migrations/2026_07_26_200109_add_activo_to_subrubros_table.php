<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuestionario I3.0 (Catalogos Contables): borrar un subrubro con
 * movimientos historicos lo borraba en cascada (FK onDelete cascade en
 * movimientos_operativos/cashflow_movimientos). Se reemplaza el borrado
 * fisico por desactivacion logica, igual que Deporte/Grupo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('es_reservado_sistema');
        });
    }

    public function down(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
