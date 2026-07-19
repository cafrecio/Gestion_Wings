<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea las cuentas de acceso base. NO se ejecuta automáticamente desde
 * DatabaseSeeder: correrlo pisa la contraseña de usuarios existentes.
 *
 * Uso (solo en instalaciones nuevas / entornos de desarrollo):
 *   php artisan db:seed --class=UserSeeder
 *
 * La contraseña sale de SEED_USER_PASSWORD en .env. Si no está definida,
 * se genera una al azar por usuario y se imprime en consola: no queda
 * ninguna contraseña real en el repositorio.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $fija = env('SEED_USER_PASSWORD');

        $usuarios = [
            ['name' => 'Admin Test',     'email' => 'admin@wings.com',      'rol' => User::ROL_ADMIN,     'profesor_id' => null, 'activo' => true],
            ['name' => 'Operativo Test', 'email' => 'operativo@wings.com',  'rol' => User::ROL_OPERATIVO, 'profesor_id' => null, 'activo' => true],
            ['name' => 'Operativo2',     'email' => 'operativo2@wing.com',  'rol' => User::ROL_OPERATIVO, 'profesor_id' => null, 'activo' => false],
            ['name' => 'Profesor',       'email' => 'profesor@wing.com',    'rol' => User::ROL_PROFESOR,  'profesor_id' => 1,    'activo' => true],
            ['name' => 'Profesor2',      'email' => 'profesor2@wings.com',  'rol' => User::ROL_PROFESOR,  'profesor_id' => 2,    'activo' => true],
        ];

        $this->command?->warn('Contraseñas generadas (guardalas ahora, no se vuelven a mostrar):');

        foreach ($usuarios as $datos) {
            $password = $fija ?: Str::password(16);

            User::updateOrCreate(
                ['email' => $datos['email']],
                [
                    'name'        => $datos['name'],
                    'rol'         => $datos['rol'],
                    'profesor_id' => $datos['profesor_id'],
                    'activo'      => $datos['activo'],
                    'password'    => $password, // cast 'hashed' lo hashea al guardar
                ]
            );

            $this->command?->line("  {$datos['email']} => {$password}");
        }

        if ($fija) {
            $this->command?->warn('Se usó SEED_USER_PASSWORD para todas las cuentas: es la misma para todas. Cambiala manualmente si esto no es solo para desarrollo local.');
        }
    }
}
