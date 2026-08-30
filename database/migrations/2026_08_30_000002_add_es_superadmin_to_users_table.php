<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se comprueba antes de agregar: en la base local la columna quedo
        // creada sin que la migracion se registrara, y sin esta guarda
        // cualquier migracion posterior se traba con "Duplicate column name".
        if (Schema::hasColumn('users', 'es_superadmin')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('es_superadmin')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'es_superadmin')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('es_superadmin');
        });
    }
};
