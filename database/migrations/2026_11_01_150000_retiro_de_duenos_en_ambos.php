<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T116, Carlos 01/11/2026: un retiro del cajón entra al turno, así la operativa no cierra con un
 * faltante ajeno. El sitio de prueba no corre CatalogosSeeder al desplegar: sin esto no le llega.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('subrubros')->where('nombre', 'Retiro de dueños')->where('permitido_para', 'ADMIN')
            ->update(['permitido_para' => 'OPERATIVO', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('subrubros')->where('nombre', 'Retiro de dueños')->where('permitido_para', 'OPERATIVO')
            ->update(['permitido_para' => 'ADMIN', 'updated_at' => now()]);
    }
};
