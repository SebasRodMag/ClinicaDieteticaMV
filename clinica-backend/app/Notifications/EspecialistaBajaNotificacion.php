<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EspecialistaBajaNotificacion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $nombreEspecialista,
        public string $fechaHoraCita = ''
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function viaQueues(): array
    {
        return ['mail' => 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $msg = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Cita cancelada por baja del especialista')
            ->greeting('Hola,')
            ->line("Lamentamos informarte que tu cita con el especialista {$this->nombreEspecialista} ha sido cancelada.");

        if (!empty($this->fechaHoraCita)) {
            $msg->line("Fecha y hora de la cita: {$this->fechaHoraCita}");
        }

        return $msg
            ->line('La cancelación se debe a la baja del especialista.')
            ->line('Puedes gestionar nuevas citas desde tu área de usuario.')
            ->salutation('Gracias por tu comprensión.');
    }
}