<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('configuraciones')->where('clave', 'inscripcion_fecha_corte')->delete();
    }

    public function down(): void
    {
        // No reinstalar una regla de negocio retirada. Cargos y pagos no se alteran.
    }
};
