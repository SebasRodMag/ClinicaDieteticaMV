<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder
 * Clase principal para ejecutar los seeders de la base de datos.
 * Se encarga de llamar a otros seeders específicos para poblar la base de datos.
 */
class DatabaseSeeder extends Seeder
{

    /**
     * Ejecuta los seeders para poblar la base de datos.
     * Llama a los seeders específicos en el orden necesario.
     *
     * @return void
     */
    public function run(): void
    {
    $this->call(RolesSeeder::class);
    $this->call(UserSeeder::class);
    $this->call(EspecialistaSeeder::class);
    $this->call(PacienteSeeder::class);
    $this->call(CitasSeeder::class);
    $this->call(Configuracion::class);
    }
}
