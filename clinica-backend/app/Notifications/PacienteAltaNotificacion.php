<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PacienteAltaNotificacion extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        public string $nombreEspecialista,
        public string $numeroHistorial = ''
    ) {
        // Evita enviar antes de que se confirme la transacción DB
        $this-> afterCommit = true;
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
        $email = (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Alta como paciente en la Clínica Dietética')
            ->greeting('Hola,')
            ->line("Te informamos que has sido dado/a de alta como paciente por el/la especialista {$this->nombreEspecialista}.");

        if (!empty($this->numeroHistorial)) {
            $email->line("Tu número de historial asignado es: {$this->numeroHistorial}.");
        }

        return $email
            ->line('Ya puedes acceder a tu área personal para gestionar tus citas y documentos.')
            ->salutation('Gracias por confiar en nosotros.');
    }
}