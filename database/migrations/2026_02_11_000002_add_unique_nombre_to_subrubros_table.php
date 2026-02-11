<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Freeze: subrubros.nombre es único global (case-insensitive).
 * La collation utf8mb4_unicode_ci de MariaDB ya garantiza CI.
 * No hay soft deletes, así que UNIQUE simple alcanza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->unique('nombre', 'subrubros_nombre_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->dropUnique('subrubros_nombre_unique');
        });
    }
};
