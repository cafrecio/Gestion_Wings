<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->string('dni', 20)->unique()->after('apellido');
            $table->date('fecha_nacimiento')->after('dni');
            $table->string('direccion')->after('fecha_nacimiento');
            $table->string('localidad')->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropUnique(['dni']);
            $table->dropColumn(['dni', 'fecha_nacimiento', 'direccion', 'localidad']);
        });
    }
};
