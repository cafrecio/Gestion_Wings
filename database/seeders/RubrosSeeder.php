<?php

namespace Database\Seeders;

use App\Models\Rubro;
use Illuminate\Database\Seeder;

class RubrosSeeder extends Seeder
{
    /**
     * Seed rubros base para el sistema de caja/cashflow.
     */
    public function run(): void
    {
        $rubros = [
            // INGRESOS
            [
                'nombre' => 'Cuotas',
                'tipo' => 'INGRESO',
                'observacion' => 'Cobro de cuotas mensuales de alumnos',
            ],
            [
                'nombre' => 'Ingresos Varios',
                'tipo' => 'INGRESO',
                'observacion' => 'Otros ingresos operativos (inscripciones, torneos, etc.)',
            ],
            [
                'nombre' => 'Intereses',
                'tipo' => 'INGRESO',
                'observacion' => 'Intereses generados por cuentas bancarias o plataformas de pago',
            ],

            // EGRESOS
            [
                'nombre' => 'Sueldos',
                'tipo' => 'EGRESO',
                'observacion' => 'Pagos al personal docente y administrativo',
            ],
            [
                'nombre' => 'Servicios',
                'tipo' => 'EGRESO',
                'observacion' => 'Pagos de servicios (luz, agua, internet, alquiler)',
            ],
            [
                'nombre' => 'Gastos Operativos',
                'tipo' => 'EGRESO',
                'observacion' => 'Gastos menores del día a día (limpieza, librería, insumos)',
            ],
        ];

        foreach ($rubros as $rubro) {
            Rubro::firstOrCreate(
                ['nombre' => $rubro['nombre']],
                $rubro
            );
        }
    }
}
