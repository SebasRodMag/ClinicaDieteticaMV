<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Traits\GenerarDniTelfUnico;

class UserSeeder extends Seeder
{
    use GenerarDniTelfUnico;


    /**
     * Ejecuta el seeder para crear usuarios con diferentes roles.
     * Este método crea un número específico de usuarios para cada rol:
     * - 100 usuarios normales
     * - 2 administradores
     * - 1000 pacientes
     * - 100 especialistas
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('es_ES');
        // Definimos la cantidad de usuarios a crear por rol
        $cantidadUsuario = 100;
        $cantidadAdministrador = 2;
        $cantidadPaciente = 1000;
        $cantidadEspecialista = 100;

        $this->crearUsuariosConRol($cantidadUsuario, 'usuario', $faker);
        $this->crearUsuariosConRol($cantidadAdministrador, 'administrador', $faker);
        $this->crearUsuariosConRol($cantidadPaciente, 'paciente', $faker);
        $this->crearUsuariosConRol($cantidadEspecialista, 'especialista', $faker);

        $this->command->info("Se crearon {$cantidadUsuario} usuarios normales correctamente.");
        $this->command->info("Se crearon {$cantidadAdministrador} administradores correctamente.");
        $this->command->info("Se crearon {$cantidadPaciente} pacientes correctamente.");
        $this->command->info("Se crearon {$cantidadEspecialista} especialistas correctamente.");
    }

    /**
     * Crea usuarios con un rol específico y los asocia a la base de datos.
     *
     * @param int $cantidad Cantidad de usuarios a crear.
     * @param string $rol Rol del usuario (ej. 'usuario', 'administrador', 'paciente', 'especialista').
     * @param \Faker\Generator $faker Instancia de Faker para generar datos aleatorios.
     */
    private function crearUsuariosConRol(int $cantidad, string $rol, $faker): void
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $email = "{$rol}{$i}@correo.com";

            // Se crear solo si no existe, si exite, no se tocan los demás campos
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'nombre'            => $faker->firstName(),
                    'apellidos'         => $faker->lastName(),
                    'dni_usuario'       => $this->generarDniUnico(),
                    'fecha_nacimiento'  => $faker->dateTimeBetween('-50 years', '-18 years'),
                    'telefono'          => $this->generarTelefonoUnico(),
                    'direccion'         => $faker->address(),
                    'email_verified_at' => now(),
                    'password'          => bcrypt('password'),
                    'remember_token'    => Str::random(10),
                ]
            );

            // Asegurar el rol sin duplicar
            if (!$user->hasRole($rol)) {
                $user->assignRole($rol);
            }
        }
    }

}
