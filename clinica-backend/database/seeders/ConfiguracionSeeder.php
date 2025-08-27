<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('configuracion')->insert([
            [
                'clave' => 'duracion_cita',
                'valor' => '30',//tiempo en minutos
                'descripcion' => 'Duración estándar de cada cita en minutos',
            ],
            [
                'clave' => 'precio_cita',
                'valor' => '25.00',//precio en euros
                'descripcion' => 'Precio base de cada cita',
            ],
            [
                'clave' => 'dias_no_laborables',
                'valor' => json_encode(['2025-12-25', '2025-01-01', '2025-05-01']),
                'descripcion' => 'Fechas específicas en las que no se trabaja',
            ],
            [
                'clave' => 'horario_laboral',
                'valor' => json_encode([
                    'apertura' => '08:00',//hora de inicio de la jornada
                    'cierre' => '16:00',//hora de fin de la jornada
                    'jornada_partida' => false,
                ]),
                'descripcion' => 'Horario general de atención',
            ],
            [
                'clave' => 'notificaciones_email',
                'valor' => 'false',//Idea de implementar notificaciones por email, más adelante
                'descripcion' => '¿Enviar correos recordatorios de citas?',
            ],
            [
                'clave' => 'color_tema',
                'valor' => '#28a745',//Idea de configurar el color del tema, más adelante
                'descripcion' => 'Color principal del sistema',
            ],
            [
                'clave' => 'Crear_cita_paciente',
                'valor' => 'true',
                'descripcion' => 'Permitir que un paciente pueda crear una cita',
            ],
            [
                'clave' => 'Especialidades',
                // ¡Aquí está la corrección!
                'valor' => json_encode(['Endocrinología', 'Nutrición', 'Psicología', 'Psiquiatría', 'Reumatología', 'Pediatría', 'Medicina general']),
                'descripcion' => 'Lista de especialidades para las que se presta servicio',
            ],
        ]);
    }
}
