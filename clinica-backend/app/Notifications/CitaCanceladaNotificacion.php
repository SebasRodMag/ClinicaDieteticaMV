<?php

namespace App\Notifications;

use App\Models\Cita;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class CitaCanceladaNotificacion extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cita $cita, //obtenemos la cita para poder acceder a los datos del paciente y especialista
        public string $motivo, // motivo de la cancelación
        public string $canceladaPor // 'paciente' | 'especialista'
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $fecha = optional($this->cita->fecha_hora_cita)->format('d-m-Y');
        $hora = optional($this->cita->fecha_hora_cita)->format('H:i');

        return (new MailMessage)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Cita cancelada')
            ->greeting('Hola,')
            ->line("Tu cita del {$fecha} a las {$hora} ha sido cancelada.")
            ->line("Motivo: {$this->motivo}.")
            ->line('Si necesitas reprogramar, por favor accede a tu área de citas.')
            ->salutation('Gracias.');
    }

    public function viaQueues(): array
    {
        return [
            'mail' => 'mail', // se fuerza la cola por 'mail'
        ];
    }

}
