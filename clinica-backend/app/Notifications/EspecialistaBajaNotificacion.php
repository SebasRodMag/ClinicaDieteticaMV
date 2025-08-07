<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EspecialistaBajaNotificacion extends Notification
{
    public function __construct(
        public string $nombreEspecialista,
        public string $fechaHoraCita = ''
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cita cancelada por baja del especialista')
            ->greeting('Hola,')
            ->line("Lamentamos informarte que tu cita con el especialista {$this->nombreEspecialista} ha sido cancelada.")
            ->when($this->fechaHoraCita, fn($msg) =>
                $msg->line("Fecha y hora de la cita: {$this->fechaHoraCita}")
            )
            ->line('La cancelación se debe a la baja del especialista.')
            ->line('Puedes gestionar nuevas citas desde tu área de usuario.')
            ->salutation('Gracias por tu comprensión.');
    }
}
