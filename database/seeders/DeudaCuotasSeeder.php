<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\DeudaCuota;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeudaCuotasSeeder extends Seeder
{
    /**
     * Seed deudas de cuotas mínimas para testing.
     * Crea deudas para los primeros 3 alumnos (si existen).
     */
    public function run(): void
    {
        $alumnos = Alumno::take(3)->get();

        if ($alumnos->isEmpty()) {
            $this->command->warn('No hay alumnos en la BD. Crear alumnos primero.');
            return;
        }

        $mesActual = Carbon::now();
        $montoBase = 28000.00;

        foreach ($alumnos as $alumno) {
            // Crear deuda del mes actual
            DeudaCuota::firstOrCreate(
                [
                    'alumno_id' => $alumno->id,
                    'periodo' => $mesActual->format('Y-m'),
                ],
                [
                    'alumno_id' => $alumno->id,
                    'periodo' => $mesActual->format('Y-m'),
                    'monto_original' => $montoBase,
                    'monto_pagado' => 0,
                    'estado' => DeudaCuota::ESTADO_PENDIENTE,
                ]
            );

            // Crear deuda del mes siguiente
            DeudaCuota::firstOrCreate(
                [
                    'alumno_id' => $alumno->id,
                    'periodo' => $mesActual->copy()->addMonth()->format('Y-m'),
                ],
                [
                    'alumno_id' => $alumno->id,
                    'periodo' => $mesActual->copy()->addMonth()->format('Y-m'),
                    'monto_original' => $montoBase,
                    'monto_pagado' => 0,
                    'estado' => DeudaCuota::ESTADO_PENDIENTE,
                ]
            );
        }

        $this->command->info('DeudaCuotasSeeder: Deudas creadas para ' . $alumnos->count() . ' alumnos.');
    }
}
