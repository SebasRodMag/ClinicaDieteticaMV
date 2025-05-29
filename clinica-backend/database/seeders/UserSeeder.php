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

    private $dniUsados = [];
    private $telefonoUsados = [];


    /**
     * Ejecuta el seeder para crear usuarios con diferentes roles.
     * Este método crea un número específico de usuarios para cada rol:
     * - 100 usuarios normales
     * - 2 administradores
     * - 600 pacientes
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
        $cantidadPaciente = 600;
        $cantidadEspecialista = 100;

        $this->crearUsuariosConRol($cantidadUsuario, 'usuario', $faker);
        $this->crearUsuariosConRol($cantidadAdministrador, 'administrador', $faker);
        $this->crearUsuariosConRol($cantidadPaciente, 'paciente', $faker);
        $this->crearUsuariosConRol($cantidadEspecialista, 'especialista', $faker);
    }

    /**
     * Crea usuarios con un rol específico y los asocia a la base de datos.
     *
     * @param int $cantidad Cantidad de usuarios a crear.
     * @param string $rol Rol del usuario (ej. 'usuario', 'administrador', 'paciente', 'especialista').
     * @param \Faker\Generator $faker Instancia de Faker para generar datos aleatorios.
     */
    private function crearUsuariosConRol(int $cantidad, string $rol, $faker)
    {
        for ($i = 1; $i <= $cantidad; $i++) {
            $dni = $this->generarDniUnico();
            $telefono = $this->generarTelefonoUnico();

            $nombre = $faker->firstName();
            $apellidos = $faker->lastName();

            $user = User::factory()->create([
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'email' => "{$rol}{$i}@correo.com",
                'dni_usuario' => $dni,
                'telefono' => $telefono,
                'direccion' => $faker->address,
            ]);

            $user->assignRole($rol);

            if ($rol === 'paciente') {
                Paciente::create([
                    'user_id' => $user->id,
                    'numero_historial' => strtoupper(Str::random(10)),
                    'fecha_alta' => $faker->dateTimeBetween('-2 years', 'now'),
                    'fecha_baja' => null,
                ]);
            } elseif ($rol === 'especialista') {
                Especialista::create([
                    'user_id' => $user->id,
                    'especialidad' => $faker->randomElement(['Nutrición', 'Endocrinología', 'Medicina General'])
                ]);
            }
        }
    }

}
