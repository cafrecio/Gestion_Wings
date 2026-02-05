<?php

namespace Database\Seeders;

use App\Models\ReglaPrimerPago;
use Illuminate\Database\Seeder;

class ReglaPrimerPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReglaPrimerPago::create([
            'nombre' => 'Primera quincena (1-15)',
            'dia_desde' => 1,
            'dia_hasta' => 15,
            'porcentaje' => 100.00,
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'nombre' => 'Segunda quincena (16-23)',
            'dia_desde' => 16,
            'dia_hasta' => 23,
            'porcentaje' => 70.00,
            'activo' => true,
        ]);

        ReglaPrimerPago::create([
            'nombre' => 'Fin de mes (24-31)',
            'dia_desde' => 24,
            'dia_hasta' => 31,
            'porcentaje' => 40.00,
            'activo' => true,
        ]);
    }
}
