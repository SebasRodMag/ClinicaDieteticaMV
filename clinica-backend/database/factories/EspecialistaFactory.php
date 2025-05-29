<?php

namespace Database\Factories;

use App\Models\Especialista;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Esta clase es una fábrica para crear instancias del modelo Especialista.
 * Utiliza la librería Faker para generar datos aleatorios.
 * La fábrica define los atributos por defecto para un especialista, incluyendo la relación con un usuario.
 * @property int $user_id clave foránea a la tabla users
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
