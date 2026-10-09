<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

class RegistroReporteDisponible
{
    public static function existe(): bool
    {
        // El desarrollo aún no migró la base del club. Sus recorridos existentes
        // deben seguir operando hasta activar el nuevo registro en ese ambiente.
        return Schema::hasTable('reporte_eventos');
    }
}
