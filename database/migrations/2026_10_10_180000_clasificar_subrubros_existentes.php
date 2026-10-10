<?php

use App\Support\ClasificacionSubrubros;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // T16: la migración del 09/10 solo clasificó sueldos. En una instalación que ya
        // existía, las cuotas cobradas no sumaban en Inicio ni en Reportes.
        if (!Schema::hasColumn('subrubros', 'clasificacion_resultado')) return;
        // Una base nueva todavía no tiene catálogo: se lo da CatalogosSeeder, ya clasificado.
        if (!DB::table('rubros')->exists()) return;

        ClasificacionSubrubros::aplicarCatalogo();
    }

    public function down(): void
    {
        // No se vuelve atrás: borrar la clasificación dejaría Inicio otra vez en cero.
    }
};
