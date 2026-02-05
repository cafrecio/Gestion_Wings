<?php

namespace Database\Seeders;

use App\Models\Rubro;
use App\Models\Subrubro;
use Illuminate\Database\Seeder;

class SubrubrosSeeder extends Seeder
{
    /**
     * Seed subrubros base para el sistema de caja/cashflow.
     *
     * permitido_para: ADMIN = solo cashflow, OPERATIVO = caja operativa
     * afecta_caja: true = pasa por caja operativa, false = solo cashflow
     */
    public function run(): void
    {
        $subrubros = [
            // INGRESOS - Cuotas (RESERVADO - solo usable por el sistema)
            'Cuotas' => [
                ['nombre' => 'Cuota Mensual', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'es_reservado_sistema' => true],
            ],

            // INGRESOS - Ingresos Varios
            'Ingresos Varios' => [
                ['nombre' => 'Inscripción Torneo', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ['nombre' => 'Venta de Indumentaria', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
            ],

            // INGRESOS - Intereses
            'Intereses' => [
                ['nombre' => 'Intereses Mercado Pago', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ['nombre' => 'Intereses Banco', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
            ],

            // EGRESOS - Sueldos
            'Sueldos' => [
                ['nombre' => 'Sueldo Patín - Romina', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ['nombre' => 'Sueldo Hockey - Lucas', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ['nombre' => 'Sueldo Administrativo', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
            ],

            // EGRESOS - Servicios
            'Servicios' => [
                ['nombre' => 'Alquiler', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ['nombre' => 'Luz', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ['nombre' => 'Internet', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
            ],

            // EGRESOS - Gastos Operativos
            'Gastos Operativos' => [
                ['nombre' => 'Limpieza', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ['nombre' => 'Librería', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ['nombre' => 'Insumos Varios', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
            ],
        ];

        foreach ($subrubros as $rubroNombre => $items) {
            $rubro = Rubro::where('nombre', $rubroNombre)->first();

            if (!$rubro) {
                $this->command->warn("Rubro '{$rubroNombre}' no encontrado. Ejecutar RubrosSeeder primero.");
                continue;
            }

            foreach ($items as $item) {
                Subrubro::updateOrCreate(
                    [
                        'rubro_id' => $rubro->id,
                        'nombre' => $item['nombre'],
                    ],
                    [
                        'permitido_para' => $item['permitido_para'],
                        'afecta_caja' => $item['afecta_caja'],
                        'es_reservado_sistema' => $item['es_reservado_sistema'] ?? false,
                    ]
                );
            }
        }
    }
}
