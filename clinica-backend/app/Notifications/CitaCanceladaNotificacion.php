<?php

namespace App\Notifications;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CitaCanceladaNotificacion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cita $cita,
        public string $motivo,
        public string $canceladaPor // 'paciente' | 'especialista'
    ) {}

    public function via($notifiable): array
    {
        return ['mail']; // y 'database' si quieres guardarla también
    }

    public function toMail($notifiable): MailMessage
    {
        $fecha = optional($this->cita->fecha_hora_cita)->format('d-m-Y');
        $hora  = optional($this->cita->fecha_hora_cita)->format('H:i');

        return (new MailMessage)
            ->subject('Cita cancelada')
            ->greeting('Hola,')
            ->line("Tu cita del {$fecha} a las {$hora} ha sido cancelada.")
            ->line("Motivo: {$this->motivo}.")
            ->line('Si necesitas reprogramar, por favor accede a tu área de citas.')
            ->salutation('Gracias.');
    }
}
