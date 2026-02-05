<?php

namespace Database\Seeders;

use App\Models\TipoCaja;
use Illuminate\Database\Seeder;

class TiposCajaSeeder extends Seeder
{
    /**
     * Seed tipos de caja base.
     */
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Efectivo', 'activo' => true],
            ['nombre' => 'Banco', 'activo' => true],
            ['nombre' => 'Mercado Pago', 'activo' => true],
        ];

        foreach ($tipos as $tipo) {
            TipoCaja::firstOrCreate(
                ['nombre' => $tipo['nombre']],
                $tipo
            );
        }
    }
}
