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
        Schema::table('clases', function (Blueprint $table) {
            $table->boolean('validada_para_liquidacion')
                ->default(false)
                ->after('hora_fin');

            $table->boolean('cancelada')
                ->default(false)
                ->after('validada_para_liquidacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropColumn(['validada_para_liquidacion', 'cancelada']);
        });
    }
};
