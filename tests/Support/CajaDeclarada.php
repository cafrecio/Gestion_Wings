<?php

namespace Tests\Support;

use App\Models\CajaOperativa;

/** Fixture explícito de un turno ya abierto y declarado; nunca se activa automáticamente. */
final class CajaDeclarada
{
    public static function crear(int $operativoId, int $tipoEfectivoId): CajaOperativa
    {
        return CajaOperativa::create([
            'usuario_operativo_id' => $operativoId, 'usuario_apertura_id' => $operativoId,
            'apertura_at' => now(), 'estado' => 'ABIERTA',
            'tipo_caja_efectivo_id' => $tipoEfectivoId, 'efectivo_inicial' => 0,
        ]);
    }
}
