<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('valor', 255);
            $table->string('descripcion')->nullable();
            $table->string('tipo', 20)->default('string');
            $table->timestamps();
        });

        DB::table('configuraciones')->insert([
            [
                'clave'       => 'dias_gracia_cobranza',
                'valor'       => '10',
                'descripcion' => 'Días del mes hasta los cuales un alumno se considera Al día sin haber pagado',
                'tipo'        => 'integer',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'clave'       => 'dia_generacion_deuda',
                'valor'       => '1',
                'descripcion' => 'Día del mes en que se genera automáticamente la deuda mensual',
                'tipo'        => 'integer',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
    }
};
