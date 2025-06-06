<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Paciente;

/**
 * PacienteSeeder
 * Clase para poblar la tabla de pacientes con datos de prueba.
 * Utiliza Faker para generar datos aleatorios y asigna pacientes a usuarios con el rol 'paciente'.
 */
class PacienteSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('es_ES');

        $usuariosPaciente = User::role('paciente')->get();

        $creados = 0;
        foreach ($usuariosPaciente as $usuario) {
            //Verificar si ya tiene paciente para evitar duplicados
            if ($usuario->paciente) {
                continue;
            }

            Paciente::create([
                'user_id' => $usuario->id,
                'numero_historial' => strtoupper($faker->bothify('??######??')),
                'fecha_alta' => $faker->dateTimeBetween('-2 years', 'now'),
                'fecha_baja' => $faker->boolean(20) ? $faker->dateTimeBetween('now', '+1 year') : null,
            ]);
            $creados++;
        }
        $this->command->info("Se crearon {$creados} pacientes correctamente.");
    }
}
