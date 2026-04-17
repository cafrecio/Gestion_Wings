<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar nivel_id nullable primero (para poder migrar datos)
        Schema::table('grupos', function (Blueprint $table) {
            $table->foreignId('nivel_id')->nullable()->after('deporte_id')
                  ->constrained('niveles')->nullOnDelete();
        });

        // 2. Crear los niveles base desde los datos existentes
        $principiantes = DB::table('niveles')->insertGetId([
            'nombre'      => 'Principiantes',
            'descripcion' => 'Alumnos que inician la actividad',
            'created_at'  => now(), 'updated_at' => now(),
        ]);
        $avanzados = DB::table('niveles')->insertGetId([
            'nombre'      => 'Avanzados',
            'descripcion' => 'Alumnos con experiencia previa',
            'created_at'  => now(), 'updated_at' => now(),
        ]);
        $federadas = DB::table('niveles')->insertGetId([
            'nombre'      => 'Federadas',
            'descripcion' => 'Competición federada',
            'created_at'  => now(), 'updated_at' => now(),
        ]);

        // 3. Asignar nivel_id a cada grupo por su nombre actual
        DB::table('grupos')->where('id', 1)->update(['nivel_id' => $principiantes]);
        DB::table('grupos')->where('id', 2)->update(['nivel_id' => $avanzados]);
        DB::table('grupos')->where('id', 3)->update(['nivel_id' => $principiantes]);
        DB::table('grupos')->where('id', 5)->update(['nivel_id' => $federadas]);

        // 4. Reasignar alumnos del grupo duplicado (id=4) al grupo correcto (id=3)
        DB::table('alumnos')->where('grupo_id', 4)->update(['grupo_id' => 3]);

        // 5. Eliminar alumno_planes del grupo duplicado (FK impide borrar grupo_planes directamente)
        $planesGrupo4 = DB::table('grupo_planes')->where('grupo_id', 4)->pluck('id');
        if ($planesGrupo4->isNotEmpty()) {
            DB::table('alumno_planes')->whereIn('plan_id', $planesGrupo4)->delete();
            DB::table('grupo_planes')->where('grupo_id', 4)->delete();
        }

        // 6. Eliminar el grupo duplicado
        DB::table('grupos')->where('id', 4)->delete();

        // 7. Hacer nivel_id NOT NULL y agregar unique constraint
        // Primero dropeamos el FK con SET NULL (no acepta NOT NULL), luego re-agregamos
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropForeign(['nivel_id']);
        });
        DB::statement('ALTER TABLE grupos MODIFY nivel_id bigint unsigned NOT NULL');
        Schema::table('grupos', function (Blueprint $table) {
            $table->foreign('nivel_id')->references('id')->on('niveles');
            $table->unique(['deporte_id', 'nivel_id'], 'grupos_deporte_nivel_unique');
        });

        // 8. Eliminar columna nombre
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->dropUnique('grupos_deporte_nivel_unique');
            $table->dropForeign(['nivel_id']);
            $table->dropColumn('nivel_id');
            $table->string('nombre', 255)->default('')->after('deporte_id');
        });
        Schema::dropIfExists('niveles');
    }
};
