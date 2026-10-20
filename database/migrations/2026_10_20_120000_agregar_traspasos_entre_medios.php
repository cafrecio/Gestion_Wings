<?php

use App\Support\ClasificacionSubrubros;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ClasificacionSubrubros::aplicarTraspasos();
    }

    public function down(): void
    {
        // Catálogo con posibles movimientos: no borrar historia al revertir código.
    }
};
