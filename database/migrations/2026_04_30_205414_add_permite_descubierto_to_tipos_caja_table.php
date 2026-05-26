<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->boolean('permite_descubierto')->default(false)->after('descripcion');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->dropColumn('permite_descubierto');
        });
    }
};
