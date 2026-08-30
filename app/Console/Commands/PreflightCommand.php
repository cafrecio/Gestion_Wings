<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PreflightCommand extends Command
{
    protected $signature = 'wings:preflight';

    protected $description = 'Verifica que la configuración y las cuentas sean aptas para producción';

    private const EMAILS_DE_PRUEBA = [
        'test@example.com',
        'admin@wings.com',
        'operativo@wings.com',
        'operativo2@wing.com',
        'profesor@wing.com',
        'profesor2@wings.com',
    ];

    public function handle(): int
    {
        $conexion = (string) config('database.default');
        $usuarioBase = (string) config("database.connections.{$conexion}.username");
        $dominioSesion = config('session.domain');

        $chequeos = [
            ['APP_ENV=production', config('app.env') === 'production', 'El perfil de desarrollo no puede servir en internet.'],
            ['APP_DEBUG=false', config('app.debug') === false, 'Un error podría mostrar código y claves.'],
            ['APP_KEY presente', filled(config('app.key')), 'Las sesiones y el cifrado quedarían inseguros.'],
            ['DB_USERNAME dedicado', $usuarioBase !== '' && mb_strtolower($usuarioBase) !== 'root', 'La aplicación no debe conectarse como administrador de la base.'],
            ['APP_URL con HTTPS', str_starts_with((string) config('app.url'), 'https://'), 'Las credenciales viajarían sin TLS.'],
            ['SESSION_SECURE_COOKIE=true', config('session.secure') === true, 'La cookie de sesión podría viajar sin HTTPS.'],
            ['SESSION_ENCRYPT=true', config('session.encrypt') === true, 'El contenido de la sesión no estaría cifrado.'],
            ['SESSION_SAME_SITE=strict', config('session.same_site') === 'strict', 'La cookie admitiría más contexto entre sitios del necesario.'],
            ['SESSION_DOMAIN vacío', $dominioSesion === null || $dominioSesion === '', 'La sesión podría compartirse entre subdominios.'],
            ['LOG_LEVEL=warning', config('logging.channels.single.level') === 'warning', 'Producción registraría ruido o información de diagnóstico.'],
            ['Sin cuentas de prueba', !User::whereIn('email', self::EMAILS_DE_PRUEBA)->exists(), 'Quedaría una cuenta conocida en producción.'],
            ['Administrador activo presente', User::where('rol', User::ROL_ADMIN)->where('activo', true)->exists(), 'Nadie podría administrar el sistema.'],
        ];

        $fallos = 0;
        foreach ($chequeos as [$nombre, $correcto, $motivo]) {
            if ($correcto) {
                $this->line("[OK] {$nombre}");
                continue;
            }

            $fallos++;
            $this->error("[ERROR] {$nombre}: {$motivo}");
        }

        if ($fallos > 0) {
            $this->newLine();
            $this->error("Preflight rechazado: {$fallos} verificación(es) fallaron.");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Preflight aprobado.');
        return self::SUCCESS;
    }
}
