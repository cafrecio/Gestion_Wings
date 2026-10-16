<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['mora_dia_desde', '10', 'Último día del mes sin recargo por mora', 'integer'],
            ['mora_porcentaje', '0', 'Porcentaje de recargo por mora; cero no genera recargos', 'string'],
        ] as [$clave, $valor, $descripcion, $tipo]) {
            DB::table('configuraciones')->insertOrIgnore(compact('clave', 'valor', 'descripcion', 'tipo'));
        }

        $rubro = DB::table('rubros')->where('nombre', 'Punitorios')->first();
        if ($rubro && $rubro->tipo !== 'INGRESO') {
            throw new RuntimeException('El rubro Punitorios existente no es INGRESO. Revisar antes de migrar.');
        }
        $id = $rubro?->id ?? DB::table('rubros')->insertGetId([
            'nombre' => 'Punitorios', 'tipo' => 'INGRESO', 'observacion' => 'Recargos por mora de cuotas',
            'es_reservado_sistema' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('rubros')->where('id', $id)->update(['es_reservado_sistema' => true]);
        $subrubro = DB::table('subrubros')->where('nombre', 'Punitorio Cuota')->first();
        if ($subrubro && (int) $subrubro->rubro_id !== (int) $id) {
            throw new RuntimeException('Punitorio Cuota pertenece a otro rubro. Revisar antes de migrar.');
        }
        DB::table('subrubros')->updateOrInsert(['nombre' => 'Punitorio Cuota'], [
            'rubro_id' => $id, 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true,
            'es_reservado_sistema' => true, 'clasificacion_resultado' => 'NEGOCIO', 'activo' => true,
            'created_at' => $subrubro?->created_at ?? now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (DB::table('cargos_alumno')->where('tipo', 'PUNITORIO')->exists()) {
            throw new RuntimeException('No se puede retirar la configuración de mora mientras existan recargos.');
        }
        DB::table('subrubros')->where('nombre', 'Punitorio Cuota')->delete();
        DB::table('rubros')->where('nombre', 'Punitorios')->delete();
        DB::table('configuraciones')->whereIn('clave', ['mora_dia_desde', 'mora_porcentaje'])->delete();
    }
};
