<?php

use App\Models\Configuracion;
use Illuminate\Database\Migrations\Migration;

/**
 * A dónde van los avisos operativos del sistema.
 *
 * Van en `configuraciones` y no en el `.env` porque **no son del servidor, son
 * del club**: los del servidor —que falló el respaldo, que se cayó el sitio— le
 * llegan a Carlos por los scripts, con sus secretos en `/etc/wings-monitor`.
 * Estos otros —que alguien cargó un cobro con fecha de un mes cerrado— le tienen
 * que llegar a quien administra el club, y ese destino lo cambia el ADMIN desde
 * la pantalla el día que cambie de teléfono o de encargada.
 *
 * El token del robot sí queda en el `.env`: eso es una credencial, no un
 * destinatario. Quien tiene el token puede escribir a cualquier chat.
 *
 * La pantalla de configuración solo edita claves que ya existen, así que se
 * crean acá aunque nazcan vacías. Vacías, el aviso no se manda y queda anotado
 * en el log: en una instalación nueva eso es lo correcto, no un error.
 */
return new class extends Migration
{
    private const CLAVES = [
        [
            'clave'       => 'avisos_telegram_chat_id',
            'valor'       => '',
            'tipo'        => 'string',
            'descripcion' => 'Chat de Telegram al que llegan los avisos operativos. Vacío: no se envían.',
        ],
        [
            'clave'       => 'avisos_email',
            'valor'       => '',
            'tipo'        => 'string',
            'descripcion' => 'Correo al que llegan los avisos operativos. Vacío: se usan los correos de los ADMIN.',
        ],
    ];

    public function up(): void
    {
        foreach (self::CLAVES as $fila) {
            if (! Configuracion::where('clave', $fila['clave'])->exists()) {
                Configuracion::create($fila);
            }
        }
    }

    public function down(): void
    {
        Configuracion::whereIn('clave', array_column(self::CLAVES, 'clave'))->delete();
    }
};
