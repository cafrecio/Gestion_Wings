<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->boolean('es_reservado_sistema')->default(false)->after('afecta_caja');
        });
    }

    public function down(): void
    {
        Schema::table('subrubros', function (Blueprint $table) {
            $table->dropColumn('es_reservado_sistema');
        });
    }
};
