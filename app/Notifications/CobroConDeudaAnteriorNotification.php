<?php

namespace App\Notifications;

use App\Models\Alumno;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CobroConDeudaAnteriorNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Alumno $alumno,
        private int $pagoId,
        private array $periodos,
        private float $montoPendiente,
        private string $motivo,
        private User $operativo
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cobro registrado con deuda anterior pendiente')
            ->greeting('Aviso de excepción de cobranza')
            ->line("Se registró el pago #{$this->pagoId} de {$this->alumno->apellido}, {$this->alumno->nombre}.")
            ->line('Quedaron pendientes los períodos: ' . implode(', ', $this->periodos) . '.')
            ->line('Monto anterior pendiente: $' . number_format($this->montoPendiente, 0, ',', '.'))
            ->line("Motivo: {$this->motivo}")
            ->line("Registrado por: {$this->operativo->name}.");
    }
}
