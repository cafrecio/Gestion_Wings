<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // B10: a donde transferirle. Vivia en el telefono de quien paga.
        // Un solo campo: un CBU o CVU (22 numeros) o un alias. Opcional.
        Schema::table('profesores', function (Blueprint $table) {
            $table->string('cbu_alias', 30)->nullable()->after('telefono');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('cbu_alias', 30)->nullable()->after('subrubro_id');
        });
    }

    public function down(): void
    {
        Schema::table('profesores', fn (Blueprint $table) => $table->dropColumn('cbu_alias'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('cbu_alias'));
    }
};
