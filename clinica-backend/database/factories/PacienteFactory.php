<?php

namespace Database\Factories;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;
/**
 * Esta clase es una fábrica para crear instancias del modelo Paciente.
 * Utiliza la librería Faker para generar datos aleatorios y la clase Carbon para manejar fechas.
 * La fábrica define los atributos por defecto para un paciente, incluyendo la relación con un usuario,
 * un número de historial médico único, y las fechas de alta y baja.
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Paciente>
 * @property int $user_id clave foránea a la tabla users
 * @property string $numero_historial clave foránea a la tabla historial_medico
 * @property \Illuminate\Support\Carbon $fecha_alta fecha de alta en el sistema
 * @property \Illuminate\Support\Carbon|null $fecha_baja fecha de baja en el sistema si es que aplica
 */
class PacienteFactory extends Factory
{
    protected $model = Paciente::class;

    /**
     * Define el estado por defecto del modelo.
     * Esta función define los atributos por defecto para un nuevo paciente.
     * Los atributos incluyen el usuario asociado, un número de historial médico único,
     * la fecha de alta y la fecha de baja (por defecto null).
     * @return array<string, mixed> devuelve un array con los atributos por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), //crea un usuario automáticamente
            'numero_historial' => $this->faker->unique()->numerify('HIST-#####'),
            'fecha_alta' => Carbon::now()->subMonths(rand(0, 12)),
            'fecha_baja' => null, //por defecto null en un paciente activo
        ];
    }
}
