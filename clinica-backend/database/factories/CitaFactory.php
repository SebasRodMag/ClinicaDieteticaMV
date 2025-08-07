<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Especialista;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CitaFactory extends Factory
{
    protected $model = Cita::class;

    //Días festivos para evitar en las citas
    protected $festivos = [
        '2025-01-01',
        '2025-05-01',
        '2025-12-25',
        '2026-01-01',
        '2026-05-01',
        '2026-12-25',
    ];

    /**
     * Define el modelo de la fábrica.
     * Esta función define los atributos por defecto para una nueva cita.
     * Los atributos incluyen el paciente, el especialista, la fecha y hora de la cita,
     * el estado de la cita, un comentario, si es la primera cita y el tipo de cita.
     * @return array los atributos por defecto para una nueva cita.
     */
    public function definition(): array
    {
        return [
            //Si no hay Paciente o Especialista, se crean con sus factories
            'id_paciente' => Paciente::factory(),
            'id_especialista' => Especialista::factory(),

            //día laboral y no festivo
            'fecha_hora_cita' => $this->obtenerFechaValidaConHora(),

            'estado' => 'pendiente',
            'comentario' => $this->faker->sentence(),
            'es_primera' => true,
            'tipo_cita' => $this->faker->randomElement(['presencial', 'telemática']),
        ];
    }

    /**
     * Genera una cita con fecha y hora válidas.
     * Esta función asegura que la cita no caiga en un fin de semana o festivo.
     * @return \Illuminate\Database\Eloquent\Factories\Factory de la cita con fecha y hora válidas.
     */
    public function primera()
    {
        return $this->state(function (array $attributes) {
            return [
                'es_primera' => true,
            ];
        });
    }

    /**
     * Obtiene una fecha válida para la cita, evitando fines de semana y festivos.
     * @return Carbon fecha válida con hora aleatoria dentro del horario laboral.
     */
    private function obtenerFechaValidaConHora(): Carbon
    {
        $fecha = $this->obtenerFechaValida();

        $horaInicio = Carbon::createFromTime(8, 0);
        $bloque = rand(0, 13); //Horas de 8:00 a 14:30 en bloques de 30 minutos
        $hora = $horaInicio->copy()->addMinutes($bloque * 30);

        return $fecha->copy()->setTimeFromTimeString($hora->format('H:i:s'));
    }



    /**
     * Obtiene una fecha válida para la cita, evitando fines de semana y festivos.
     * @return Carbon fecha válida.
     */
    private function obtenerFechaValida(): Carbon
    {
        $fecha = Carbon::now()->addDays(rand(0, 30))->startOfDay();

        while ($this->esFinDeSemana($fecha) || $this->esFestivo($fecha)) {
            $fecha->addDay();
        }

        return $fecha;
    }


    /**
     * Verifica si la fecha es un fin de semana.
     * @param Carbon $fecha fecha a verificar.
     * @return bool true si es fin de semana, false en caso contrario.
     */
    private function esFinDeSemana(Carbon $fecha): bool
    {
        return $fecha->isWeekend();
    }


    /**
     * Verifica si la fecha es un festivo.
     * Compara la fecha con una lista de días festivos predefinidos.
     * @param Carbon $fecha fecha a verificar.
     * @return bool true si es festivo, false en caso contrario.
     */
    private function esFestivo(Carbon $fecha): bool
    {
        return in_array($fecha->toDateString(), $this->festivos);
    }
}
