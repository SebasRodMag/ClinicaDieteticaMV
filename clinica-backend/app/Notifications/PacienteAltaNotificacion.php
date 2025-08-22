<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PacienteAltaNotificacion extends Notification
{
    public function __construct(
        public string $nombreEspecialista,
        public string $numeroHistorial = ''
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alta como paciente en la Clínica Dietética')
            ->greeting('Hola,')
            ->line("Te informamos que has sido dado de alta como paciente por el especialista {$this->nombreEspecialista}.")
            ->when(
                $this->numeroHistorial,
                fn($msg) =>
                $msg->line("Tu número de historial asignado es: {$this->numeroHistorial}.")
            )
            ->line('A partir de ahora podrás acceder a tu área personal para gestionar tus citas y documentos.')
            ->salutation('Gracias por confiar en nosotros.');
    }
}
