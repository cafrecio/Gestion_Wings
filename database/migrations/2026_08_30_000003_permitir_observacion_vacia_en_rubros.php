<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La observacion de un rubro es opcional: el formulario lo dice literalmente
 * ("Observacion opcional...") y la validacion la acepta vacia. Pero la columna
 * era NOT NULL, asi que guardar un rubro sin observacion terminaba en una
 * pantalla de error 500 en vez de guardarse.
 *
 * Se corrige la base, no la validacion: el campo tiene que seguir siendo opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            $table->text('observacion')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Volver atras exigiria inventar un texto para las filas que quedaron
        // vacias. No se revierte.
    }
};
