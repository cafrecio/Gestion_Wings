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
        Schema::table('profesores', function (Blueprint $table) {
            $table->foreignId('deporte_id')
                ->nullable()
                ->after('id')
                ->constrained('deportes')
                ->onDelete('restrict');

            $table->decimal('valor_hora', 10, 2)
                ->nullable()
                ->after('telefono');

            $table->decimal('porcentaje_comision', 5, 2)
                ->nullable()
                ->after('valor_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profesores', function (Blueprint $table) {
            $table->dropForeign(['deporte_id']);
            $table->dropColumn(['deporte_id', 'valor_hora', 'porcentaje_comision']);
        });
    }
};
