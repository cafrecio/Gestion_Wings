<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * D1 — El UNIQUE(alumno_id, activo) con `activo` NOT NULL impedía cambiar
 * de plan más de una vez: al cerrar el segundo plan con activo=0 chocaba
 * con el primero ya cerrado en activo=0. Solución: `activo` nullable y
 * los planes cerrados pasan a NULL (MySQL permite múltiples NULL en un
 * índice único), manteniendo la garantía de un solo plan activo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumno_planes', function (Blueprint $table) {
            $table->boolean('activo')->nullable()->default(true)->change();
        });

        // Planes ya cerrados (activo=0) → NULL, para no chocar entre sí en el UNIQUE.
        DB::table('alumno_planes')->where('activo', 0)->update(['activo' => null]);
    }

    public function down(): void
    {
        // Revertir NULL → 0 antes de volver la columna a NOT NULL.
        DB::table('alumno_planes')->whereNull('activo')->update(['activo' => 0]);

        Schema::table('alumno_planes', function (Blueprint $table) {
            $table->boolean('activo')->nullable(false)->default(true)->change();
        });
    }
};
