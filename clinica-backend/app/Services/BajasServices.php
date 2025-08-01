<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use App\Models\User;
use App\Notifications\CitaCanceladaNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class BajasServices
{
    /**
     * Baja de paciente: cambia rol a 'usuario', elimina citas y notifica a especialistas.
     */
    public function darDeBajaPaciente(Paciente $paciente, User $actor, bool $eliminarCitasPasadas = false): void
    {
        DB::transaction(function () use ($paciente, $actor, $eliminarCitasPasadas) {
            //Cambiar rol del usuario asociado
            $this->cambiarRolABasico($paciente->user ?? $paciente->usuario);

            //Citas del paciente
            $query = Cita::where('paciente_id', $paciente->id);
            if (!$eliminarCitasPasadas) {
                $query->where('fecha_hora_cita', '>=', Carbon::now());
            }
            $citas = $query->get();

            //Notificar a cada especialista implicado
            foreach ($citas as $cita) {
                $especialista = $cita->especialista;
                $destinatario = $especialista?->user ?? $especialista?->usuario;
                if ($destinatario) {
                    Notification::send($destinatario, new CitaCanceladaNotification(
                        cita: $cita,
                        motivo: 'El paciente ha sido dado de baja',
                        canceladaPor: 'paciente'
                    ));
                }
            }

            //Eliminar citas
            Cita::whereIn('id', $citas->pluck('id'))->delete();

            //Eliminamos el paciente de la tabla pacientes, pero no el usuario.
            method_exists($paciente, 'forceDelete') ? $paciente->forceDelete() : $paciente->delete();
        });
    }

    /**
     * Baja de especialista: cambia rol a 'usuario', elimina citas y notifica a pacientes.
     */
    public function darDeBajaEspecialista(Especialista $especialista, User $actor, bool $eliminarCitasPasadas = false): void
    {
        DB::transaction(function () use ($especialista, $actor, $eliminarCitasPasadas) {
            //Cambiar rol del usuario asociado
            $this->cambiarRolABasico($especialista->user ?? $especialista->usuario);

            //Citas del especialista
            $query = \App\Models\Cita::where('id_especialista', $especialista->id)
                ->orWhere('especialista_id', $especialista->id);
            if (!$eliminarCitasPasadas) {
                $query->where('fecha_hora_cita', '>=', Carbon::now());
            }
            $citas = $query->get();

            //Notificar a cada paciente implicado
            foreach ($citas as $cita) {
                $paciente = $cita->paciente;
                $destinatario = $paciente?->user ?? $paciente?->usuario;
                if ($destinatario) {
                    Notification::send($destinatario, new CitaCanceladaNotification(
                        cita: $cita,
                        motivo: 'El especialista ha sido dado de baja',
                        canceladaPor: 'especialista'
                    ));
                }
            }

            // Eliminar citas (hard delete)
            Cita::whereIn('id', $citas->pluck('id'))->delete();

            //Eliminamos al especialista de la tabla especialistas, pero no al usuario.
            method_exists($especialista, 'forceDelete') ? $especialista->forceDelete() : $especialista->delete();
        });
    }

    /**
     * Cambia el rol del usuario a 'usuario' (básico).
     */
    private function cambiarRolABasico($user): void
    {
        if (!$user)
            return;

        //Como utilizo Spatie
        if (method_exists($user, 'syncRoles')) {
            $user->syncRoles(['usuario']);
            return;
        }
    }
}
