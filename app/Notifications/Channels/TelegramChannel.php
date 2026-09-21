<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manda el aviso al mismo robot de Telegram que usan los scripts del servidor.
 *
 * Sin paquete de terceros a propósito: es una llamada HTTP de diez líneas y el
 * robot ya existe. Agregar una dependencia para esto costaría más de lo que
 * resuelve.
 *
 * Tres decisiones que conviene conocer antes de tocarlo:
 *
 * **Un aviso que falla nunca rompe la operación.** Se avisa *después* de que el
 * movimiento ya quedó guardado; si Telegram está caído, el club tiene que poder
 * seguir cobrando igual. Por eso todo el envío va dentro de un try y lo peor que
 * pasa es una línea en el log.
 *
 * **Va sincrónico, no por cola.** Hoy no hay ningún worker corriendo: una
 * notificación encolada no saldría nunca y nadie se enteraría, que es peor que
 * esperar dos segundos. El timeout corto acota ese costo. Si algún día el volumen
 * lo justifica, se pasa a cola y se agrega el worker al scheduler — las dos cosas
 * juntas, no una sola.
 *
 * **Sin configurar, no es un error.** En una máquina de desarrollo no hay token:
 * el canal lo dice en el log y sigue. Fallar ahí llenaría de ruido el trabajo
 * diario sin proteger nada.
 */
class TelegramChannel
{
    /** Telegram responde en menos de un segundo; más que esto es que no está. */
    private const TIMEOUT_SEGUNDOS = 4;

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $mensaje = trim((string) $notification->toTelegram($notifiable));
        if ($mensaje === '') {
            return;
        }

        // El token es una credencial y vive en el `.env`. El chat es el
        // destinatario y vive en `configuraciones`, para que el ADMIN lo cambie
        // desde la pantalla cuando cambie de teléfono o de encargada, sin que
        // nadie toque un archivo del servidor. El `.env` queda solo de respaldo
        // para una instalación que todavía no configuró nada.
        $token = config('services.telegram.bot_token');
        $chat  = \App\Models\Configuracion::get('avisos_telegram_chat_id')
            ?: config('services.telegram.chat_id');

        if (empty($token) || empty($chat)) {
            Log::info('Telegram sin configurar: el aviso no se envió.', [
                'aviso' => class_basename($notification),
            ]);
            return;
        }

        try {
            $respuesta = Http::timeout(self::TIMEOUT_SEGUNDOS)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chat,
                    'text'    => $mensaje,
                ]);

            if ($respuesta->failed()) {
                // El cuerpo de la respuesta puede traer el token en un mensaje de
                // error de la API: se registra el código y nada más.
                Log::warning('Telegram rechazó el aviso.', [
                    'aviso'  => class_basename($notification),
                    'codigo' => $respuesta->status(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar por Telegram.', [
                'aviso'  => class_basename($notification),
                'motivo' => $e->getMessage(),
            ]);
        }
    }
}
