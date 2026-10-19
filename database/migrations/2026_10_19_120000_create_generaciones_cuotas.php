<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T40 (DU-52): constancia de que la generación de cuotas de un mes terminó.
 *
 * Sin esta tabla no hay forma de saber si el proceso del día 1 corrió: las
 * cuotas del mes también las crean el alta y el reingreso, así que "hay cuotas
 * del mes" no prueba nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generaciones_cuotas', function (Blueprint $table) {
            $table->id();
            $table->string('periodo', 7)->unique();
            $table->timestamp('terminada_at');
            $table->unsignedInteger('creadas')->default(0);
            $table->unsignedInteger('a_revision')->default(0);
            $table->unsignedInteger('ya_existian')->default(0);
            $table->unsignedInteger('sin_plan')->default(0);
            $table->timestamps();
        });

        // El mes en curso al instalar se da por atendido: el proceso viejo ya
        // tuvo su día 1 (o la base es nueva y no hay mes que recuperar). Sin
        // esta fila, si el próximo día 1 no corre, no habría mes anterior que
        // pruebe que el sistema venía funcionando y no se reintentaría.
        // En la base de pruebas no: dependería del día real en que se corre la
        // suite, y cada prueba arma su propio historial.
        if (app()->environment('testing')) {
            return;
        }

        DB::table('generaciones_cuotas')->insert([
            'periodo' => now()->format('Y-m'),
            'terminada_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('generaciones_cuotas');
    }
};
