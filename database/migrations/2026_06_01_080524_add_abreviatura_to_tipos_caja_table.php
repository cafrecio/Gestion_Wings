<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->string('abreviatura', 5)->nullable()->after('nombre');
        });

        DB::table('tipos_caja')->where('nombre', 'like', '%Efectivo%')->update(['abreviatura' => 'EFT']);
        DB::table('tipos_caja')->where('nombre', 'like', '%Mercado%')->update(['abreviatura' => 'MP']);
        DB::table('tipos_caja')->where('nombre', 'like', '%Banco%')->update(['abreviatura' => 'BNC']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tipos_caja', function (Blueprint $table) {
            $table->dropColumn('abreviatura');
        });
    }
};
