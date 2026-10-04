<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnos', function (Blueprint $table) {
            // Solo nuevas altas: conserva decisión, autor y valores del momento.
            $table->json('alta_cuota')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('alumnos', fn (Blueprint $table) => $table->dropColumn('alta_cuota'));
    }
};
