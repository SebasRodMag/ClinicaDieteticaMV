<?php

namespace Database\Seeders;

use App\Models\Historial;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

/**
 * Seeder para poblar la tabla 'historial' con datos de prueba.
 */
class HistorialSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Faker::create();

        $pacientes = Paciente::all(['id'])->pluck('id')->toArray();
        $especialistas = Especialista::all(['id'])->pluck('id')->toArray();
        $citas = Cita::all(['id_cita'])->pluck('id_cita')->toArray();

        if (empty($pacientes) || empty($especialistas)) {
            $this->command->warn("No hay pacientes o especialistas en la base de datos. Se omite el HistorialSeeder.");
            return;
        }

        for ($i = 0; $i < 50; $i++) {
            Historial::create([
                'id_paciente' => $faker->randomElement($pacientes),
                'id_especialista' => $faker->randomElement($especialistas),
                'id_cita' => $faker->randomElement($citas),
                'fecha' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                'comentarios_paciente' => $faker->optional()->sentence(8),
                'observaciones_especialista' => $faker->optional()->paragraph(2),
                'recomendaciones' => $faker->optional()->paragraph(2),
                'dieta' => $faker->optional()->text(150),
                'lista_compra' => $faker->optional()->text(150),
            ]);
        }

        $this->command->info('Se han insertado 50 historiales de prueba correctamente.');
    }
}
