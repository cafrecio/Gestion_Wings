<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Principiantes', 'Avanzados', 'Federadas'] as $nombre) {
            $nivel = DB::table('niveles')->where('nombre', $nombre)->first();

            if (! $nivel) {
                continue;
            }

            $gruposQueLoUsan = DB::table('grupos')
                ->where('nivel_id', $nivel->id)
                ->count();

            if ($gruposQueLoUsan > 0) {
                Log::warning('Nivel heredado conservado porque tiene grupos asociados.', [
                    'nivel_id' => $nivel->id,
                    'nivel_nombre' => $nombre,
                    'grupos_asociados' => $gruposQueLoUsan,
                ]);

                continue;
            }

            DB::table('niveles')->where('id', $nivel->id)->delete();
        }
    }

    public function down(): void
    {
        // La normalización elimina solo filas sin referencias. No se recrean
        // datos legacy al revertir porque no existe una relación que restaurar.
    }
};
