<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aceptaciones_legales', function (Blueprint $table) {
            $table->id();
            // Si se borra el usuario, la constancia queda con su nombre y correo.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre');
            $table->string('email');
            $table->string('rol', 20);
            $table->string('version', 20);
            $table->char('hash_contenido', 64);
            $table->string('ip', 45)->nullable();
            $table->string('navegador')->nullable();
            $table->dateTime('aceptado_en');
            $table->timestamps();
            $table->unique(['user_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aceptaciones_legales');
    }
};
