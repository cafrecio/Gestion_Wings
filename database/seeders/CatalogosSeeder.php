<?php

namespace Database\Seeders;

use App\Models\Deporte;
use App\Models\Nivel;
use App\Models\ReglaPrimerPago;
use App\Models\Rubro;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use Illuminate\Database\Seeder;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDeportes();
        $this->seedNiveles();
        $this->seedRubrosYSubrubros();
        $this->seedTiposCaja();
        $this->seedReglasPrimerPago();
    }

    private function seedDeportes(): void
    {
        foreach ([
            ['nombre' => 'Patín', 'tipo_liquidacion' => 'HORA', 'activo' => true],
            ['nombre' => 'Fútbol', 'tipo_liquidacion' => 'COMISION', 'activo' => true],
        ] as $deporte) {
            Deporte::updateOrCreate(['nombre' => $deporte['nombre']], $deporte);
        }
    }

    private function seedNiveles(): void
    {
        foreach (['Principiantes', 'Intermedias', 'Avanzadas'] as $nombre) {
            Nivel::updateOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => null]
            );
        }
    }

    private function seedRubrosYSubrubros(): void
    {
        $rubros = [
            'Cuotas' => [
                'tipo' => 'INGRESO',
                'observacion' => 'Cobro de cuotas mensuales de alumnos',
                'subrubros' => [
                    ['nombre' => 'Cuota Mensual', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true, 'es_reservado_sistema' => true],
                ],
            ],
            'Intereses' => [
                'tipo' => 'INGRESO',
                'observacion' => 'Intereses generados por cuentas bancarias o plataformas de pago',
                'subrubros' => [
                    ['nombre' => 'Intereses Mercado Pago', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                    ['nombre' => 'Intereses Banco', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ],
            ],
            'Sueldos' => [
                'tipo' => 'EGRESO',
                'observacion' => 'Pagos al personal docente y administrativo',
                'subrubros' => [],
            ],
            'Servicios' => [
                'tipo' => 'EGRESO',
                'observacion' => 'Pagos de servicios (luz, agua, internet, alquiler)',
                'subrubros' => [
                    ['nombre' => 'Luz', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                    ['nombre' => 'Internet', 'permitido_para' => 'ADMIN', 'afecta_caja' => false],
                ],
            ],
            'Gastos Operativos' => [
                'tipo' => 'EGRESO',
                'observacion' => 'Gastos menores del día a día (limpieza, librería, insumos)',
                'subrubros' => [
                    ['nombre' => 'Limpieza', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                    ['nombre' => 'Librería', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                    ['nombre' => 'Insumos Varios', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ],
            ],
            'Alquileres' => [
                'tipo' => 'EGRESO',
                'observacion' => 'Alquileres de pistas',
                'subrubros' => [
                    ['nombre' => 'San Carlos', 'permitido_para' => 'ADMIN', 'afecta_caja' => true],
                    ['nombre' => 'Centenera', 'permitido_para' => 'ADMIN', 'afecta_caja' => true],
                    ['nombre' => 'Eventos', 'permitido_para' => 'ADMIN', 'afecta_caja' => true],
                ],
            ],
            'Torneos' => [
                'tipo' => 'INGRESO',
                'observacion' => 'Inscripciones a torneos',
                'subrubros' => [
                    ['nombre' => 'Inscripciones', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ],
            ],
            'Indumentaria' => [
                'tipo' => 'INGRESO',
                'observacion' => 'Indumentaria y accesorios en general',
                'subrubros' => [
                    ['nombre' => 'Patines', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                    ['nombre' => 'Indumentaria institucional', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                    ['nombre' => 'VG Indumentaria', 'permitido_para' => 'OPERATIVO', 'afecta_caja' => true],
                ],
            ],
        ];

        foreach ($rubros as $nombre => $datos) {
            $rubro = Rubro::updateOrCreate(
                ['nombre' => $nombre],
                ['tipo' => $datos['tipo'], 'observacion' => $datos['observacion']]
            );

            foreach ($datos['subrubros'] as $datosSubrubro) {
                Subrubro::updateOrCreate(
                    ['nombre' => $datosSubrubro['nombre']],
                    [
                        'rubro_id' => $rubro->id,
                        'permitido_para' => $datosSubrubro['permitido_para'],
                        'afecta_caja' => $datosSubrubro['afecta_caja'],
                        'es_reservado_sistema' => $datosSubrubro['es_reservado_sistema'] ?? false,
                        'activo' => true,
                    ]
                );
            }
        }
    }

    private function seedTiposCaja(): void
    {
        foreach ([
            ['nombre' => 'Efectivo', 'abreviatura' => 'EFT', 'descripcion' => null, 'permite_descubierto' => false, 'activo' => true],
            ['nombre' => 'Banco Nación', 'abreviatura' => 'BNA', 'descripcion' => 'CTA CTE Banco Nación', 'permite_descubierto' => true, 'activo' => true],
            ['nombre' => 'Mercado Pago', 'abreviatura' => 'MP', 'descripcion' => null, 'permite_descubierto' => false, 'activo' => true],
            ['nombre' => 'Banco Nacion ahorro', 'abreviatura' => null, 'descripcion' => 'Caja de ahorro Banco Nación', 'permite_descubierto' => true, 'activo' => true],
            ['nombre' => 'Banco Galicia', 'abreviatura' => 'BGA', 'descripcion' => null, 'permite_descubierto' => true, 'activo' => true],
        ] as $tipoCaja) {
            TipoCaja::updateOrCreate(['nombre' => $tipoCaja['nombre']], $tipoCaja);
        }
    }

    private function seedReglasPrimerPago(): void
    {
        foreach ([
            ['nombre' => 'Primera quincena (1-15)', 'dia_desde' => 1, 'dia_hasta' => 15, 'porcentaje' => 100.00, 'activo' => true],
            ['nombre' => 'Segunda quincena (16-23)', 'dia_desde' => 16, 'dia_hasta' => 23, 'porcentaje' => 70.00, 'activo' => true],
            ['nombre' => 'Fin de mes (24-31)', 'dia_desde' => 24, 'dia_hasta' => 31, 'porcentaje' => 40.00, 'activo' => true],
        ] as $regla) {
            ReglaPrimerPago::updateOrCreate(['nombre' => $regla['nombre']], $regla);
        }
    }
}
