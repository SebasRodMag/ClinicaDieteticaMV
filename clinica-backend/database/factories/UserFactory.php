<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\GenerarDniTelfUnico;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    use GenerarDniTelfUnico;
    /**
     * La clase del modelo que se está creando.
     * @var string clase del modelo
     * 
     */
    protected static ?string $password;

    /**
     * Define el estado por defecto del modelo.
     *
     * @return array<string, mixed> devuelve un array con los atributos por defecto del modelo
     * Esta función define los atributos por defecto para un nuevo usuario.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'dni_usuario' => $this->generarDniUnico(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'telefono' => $this->generarTelefonoUnico(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica si el usuario es un administrador.
     * Esta función establece el rol del usuario como 'admin'.
     * @return static instancia del modelo con el rol de administrador
     * 
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
