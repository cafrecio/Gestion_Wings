<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seeders de datos base para el sistema de pagos
        $this->call([
            FormaPagoSeeder::class,
            ReglaPrimerPagoSeeder::class,
        ]);

        // Seeders de datos base para caja/cashflow
        $this->call([
            RubrosSeeder::class,
            SubrubrosSeeder::class,
            TiposCajaSeeder::class,
            CashflowMovimientoSeeder::class,
        ]);
    }
}
