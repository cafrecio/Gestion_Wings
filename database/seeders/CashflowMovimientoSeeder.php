<?php

namespace Database\Seeders;

use App\Models\CashflowMovimiento;
use App\Models\Subrubro;
use App\Models\TipoCaja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CashflowMovimientoSeeder extends Seeder
{
    /**
     * Seed movimientos de cashflow mínimos para testing.
     *
     * Solo crea movimientos de subrubros ADMIN (cashflow puro).
     */
    public function run(): void
    {
        // Obtener primer usuario (asumimos que es admin para testing)
        $admin = User::first();

        if (!$admin) {
            $this->command->warn('No hay usuarios en la BD. Crear usuario primero.');
            return;
        }

        $hoy = Carbon::now()->toDateString();

        $movimientos = [
            // INGRESO - Intereses Mercado Pago → Mercado Pago
            [
                'subrubro_nombre' => 'Intereses Mercado Pago',
                'tipo_caja_nombre' => 'Mercado Pago',
                'monto' => 1500.00,
                'observaciones' => 'Rendimiento mensual MP',
            ],
            // EGRESO - Sueldo Patín - Romina → Banco
            [
                'subrubro_nombre' => 'Sueldo Patín - Romina',
                'tipo_caja_nombre' => 'Banco',
                'monto' => 45000.00,
                'observaciones' => 'Liquidación enero 2026',
            ],
            // EGRESO - Alquiler → Banco
            [
                'subrubro_nombre' => 'Alquiler',
                'tipo_caja_nombre' => 'Banco',
                'monto' => 80000.00,
                'observaciones' => 'Alquiler febrero 2026',
            ],
        ];

        foreach ($movimientos as $mov) {
            $subrubro = Subrubro::where('nombre', $mov['subrubro_nombre'])->first();
            $tipoCaja = TipoCaja::where('nombre', $mov['tipo_caja_nombre'])->first();

            if (!$subrubro) {
                $this->command->warn("Subrubro '{$mov['subrubro_nombre']}' no encontrado.");
                continue;
            }

            if (!$tipoCaja) {
                $this->command->warn("TipoCaja '{$mov['tipo_caja_nombre']}' no encontrado.");
                continue;
            }

            CashflowMovimiento::create([
                'fecha' => $hoy,
                'subrubro_id' => $subrubro->id,
                'tipo_caja_id' => $tipoCaja->id,
                'monto' => $mov['monto'],
                'observaciones' => $mov['observaciones'],
                'usuario_admin_id' => $admin->id,
                'referencia_tipo' => 'SEED',
                'referencia_id' => null,
            ]);
        }

        $this->command->info('CashflowMovimientoSeeder: 3 movimientos creados.');
    }
}
