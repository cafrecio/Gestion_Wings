<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CrearAdminCommand extends Command
{
    protected $signature = 'wings:crear-admin
                            {--superadmin : Crea una cuenta administradora protegida}';

    protected $description = 'Crea de forma interactiva la cuenta administradora inicial';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->ask('Email del administrador')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El email ingresado no es válido.');
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ya existe un usuario con ese email.');
            return self::FAILURE;
        }

        $nombre = trim((string) $this->ask('Nombre del administrador'));
        if ($nombre === '') {
            $this->error('El nombre es obligatorio.');
            return self::FAILURE;
        }

        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
        if (mb_strlen($password) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');
            return self::FAILURE;
        }

        $usuario = new User();
        $usuario->name = $nombre;
        $usuario->email = $email;
        $usuario->password = Hash::make($password);
        $usuario->rol = User::ROL_ADMIN;
        $usuario->activo = true;
        $usuario->es_superadmin = (bool) $this->option('superadmin');
        $usuario->save();

        $this->info('Administrador creado correctamente.');
        return self::SUCCESS;
    }
}
