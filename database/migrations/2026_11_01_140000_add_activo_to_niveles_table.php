<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** T102: un nivel sin grupos activos se puede inactivar para que no se ofrezca más. */
    public function up(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('niveles', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
