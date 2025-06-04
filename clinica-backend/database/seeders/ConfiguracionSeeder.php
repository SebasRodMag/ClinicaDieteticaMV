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
        DB::table('settings')->insert([
            [
                'clave' => 'duracion_cita',
                'valor' => '30',
                'descripcion' => 'Duración estándar de cada cita en minutos',
            ],
            [
                'clave' => 'precio_cita',
                'valor' => '25.00',
                'descripcion' => 'Precio base de cada cita',
            ],
            [
                'clave' => 'dias_no_laborables',
                'valor' => json_encode(['2025-06-05', '2025-12-25']),
                'descripcion' => 'Fechas específicas en las que no se trabaja',
            ],
            [
                'clave' => 'horario_laboral',
                'valor' => json_encode([
                    'apertura' => '09:00',
                    'cierre' => '17:00',
                    'jornada_partida' => false,
                ]),
                'descripcion' => 'Horario general de atención',
            ],
            [
                'clave' => 'notificaciones_email',
                'valor' => 'true',
                'descripcion' => '¿Enviar correos recordatorios de citas?',
            ],
            [
                'clave' => 'color_tema',
                'valor' => '#28a745',
                'descripcion' => 'Color principal del sistema',
            ],
        ]);
    }
}
