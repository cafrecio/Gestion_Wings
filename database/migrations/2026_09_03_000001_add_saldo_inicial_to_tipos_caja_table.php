<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->decimal('saldo_inicial', 12, 2)->default(0)->after('permite_descubierto');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->dropColumn('saldo_inicial');
        });
    }
};
