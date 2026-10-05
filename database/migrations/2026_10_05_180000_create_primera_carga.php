<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        // Club aún sin alumnos: decisión Carlos 05/10. No se infiere estado del padrón.
        Schema::create('primera_carga', function (Blueprint $t) {
            $t->unsignedTinyInteger('id')->primary();
            $t->string('estado', 20)->default('PENDIENTE');
            $t->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $t->json('detalle')->nullable();
            $t->timestamps();
        });
        DB::table('primera_carga')->insert(['id' => 1, 'estado' => 'PENDIENTE', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('primera_carga');
    }
};
