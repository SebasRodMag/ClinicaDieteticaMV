<?php

namespace Database\Factories;

use App\Models\Especialista;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Esta clase es una fábrica para crear instancias del modelo Especialista.
 * Utiliza la librería Faker para generar datos aleatorios.
 * La fábrica define los atributos por defecto para un especialista, incluyendo la relación con un usuario.
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Especialista>
 * @property int $id_usuario clave foránea a la tabla users
 * @property string $especialidad especialidad del especialista
 * @property string $numero_colegiado número de colegiado del especialista
 */
class EspecialistaFactory extends Factory
{
    protected $model = Especialista::class;


    /**
     * Define el estado por defecto del modelo.
     * Esta función define los atributos por defecto para un nuevo especialista.
     * Los atributos incluyen el usuario asociado, y otros campos específicos del especialista.
     * @return array<string, mixed> devuelve un array con los atributos por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), //Se crea un usuario automáticamente
        ];
    }
}
