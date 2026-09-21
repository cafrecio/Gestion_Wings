<?php

namespace App\Notifications;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al ADMIN de algo que pasó en la operación y conviene que sepa.
 *
 * Genérica a propósito: el primer caso es una fecha vieja cargada en caja
 * (FIN-09), pero la forma sirve igual para cualquier otro. Quien avise arma el
 * título y los datos; esta clase solo se ocupa de cómo se ve en cada canal.
 *
 * **Sobre el correo.** Hoy `mail.default` es `log`: el mensaje se escribe en el
 * archivo de log y no sale a ningún lado. Se declara igual, para que el día que
 * se configure un SMTP empiece a llegar sin tocar esto. Lo que hoy llega de
 * verdad es Telegram.
 */
class AvisoOperativo extends Notification
{
    /**
     * @param array<string,string> $datos Pares etiqueta => valor, en el orden en
     *                                    que se quieren leer.
     */
    public function __construct(
        private string $titulo,
        private array $datos = [],
        private ?string $detalle = null,
    ) {}

    /**
     * Cada destinatario usa un canal y solo uno.
     *
     * El chat de Telegram es **uno solo para todo el club**, así que el aviso se
     * manda una vez, a un destinatario sin correo creado para eso. Los
     * administradores reciben el suyo por correo.
     *
     * Devolver los dos canales para cada administrador parecía lo natural y está
     * mal: con tres administradores salían cuatro mensajes de Telegram al mismo
     * chat, tres de ellos repetidos. Lo encontró la prueba antes de que llegara
     * a ningún teléfono.
     */
    public function via(object $notifiable): array
    {
        return $this->tieneCorreo($notifiable)
            ? ['mail']
            : [TelegramChannel::class];
    }

    private function tieneCorreo(object $notifiable): bool
    {
        if (! empty($notifiable->email ?? null)) {
            return true;
        }

        return method_exists($notifiable, 'routeNotificationFor')
            && ! empty($notifiable->routeNotificationFor('mail'));
    }

    public function toTelegram(object $notifiable): string
    {
        $lineas = ['Wings: ' . $this->titulo, ''];

        foreach ($this->datos as $etiqueta => $valor) {
            $lineas[] = $etiqueta . ': ' . $valor;
        }

        if ($this->detalle) {
            $lineas[] = '';
            $lineas[] = $this->detalle;
        }

        return implode("\n", $lineas);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mensaje = (new MailMessage())
            ->subject('Wings: ' . $this->titulo)
            ->greeting('Wings');

        foreach ($this->datos as $etiqueta => $valor) {
            $mensaje->line('**' . $etiqueta . ':** ' . $valor);
        }

        if ($this->detalle) {
            $mensaje->line('')->line($this->detalle);
        }

        return $mensaje;
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'titulo'  => $this->titulo,
            'datos'   => $this->datos,
            'detalle' => $this->detalle,
        ];
    }
}
