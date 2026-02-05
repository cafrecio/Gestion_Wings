<?php

namespace Database\Seeders;

use App\Models\FormaPago;
use Illuminate\Database\Seeder;

class FormaPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FormaPago::create(['nombre' => 'Efectivo', 'activo' => true]);
        FormaPago::create(['nombre' => 'Débito', 'activo' => true]);
        FormaPago::create(['nombre' => 'Crédito', 'activo' => true]);
        FormaPago::create(['nombre' => 'Transferencia', 'activo' => true]);
    }
}
