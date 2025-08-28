<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    /**
     * Inserción de los datos iniciales en la tabla de configuración.
     */
    public function run(): void
    {
        $filas = [
            ['clave' => 'duracion_cita',       'valor' => '30',      'descripcion' => 'Duración estándar de cada cita en minutos'],
            ['clave' => 'precio_cita',         'valor' => '25.00',   'descripcion' => 'Precio base de cada cita'],
            ['clave' => 'dias_no_laborables',  'valor' => json_encode(['2025-12-25','2025-01-01','2025-05-01']), 'descripcion' => 'Fechas específicas en las que no se trabaja'],
            ['clave' => 'horario_laboral',     'valor' => json_encode(['apertura'=>'08:00','cierre'=>'16:00','jornada_partida'=>false]), 'descripcion' => 'Horario general de atención'],
            ['clave' => 'notificaciones_email','valor' => 'false',   'descripcion' => '¿Enviar correos recordatorios de citas?'],
            ['clave' => 'color_tema',          'valor' => '#28a745', 'descripcion' => 'Color principal del sistema'],
            ['clave' => 'Crear_cita_paciente', 'valor' => 'true',    'descripcion' => 'Permitir que un paciente pueda crear una cita'],
            ['clave' => 'Especialidades',      'valor' => json_encode(['Endocrinología','Nutrición','Psicología','Psiquiatría','Reumatología','Pediatría','Medicina general']), 'descripcion' => 'Lista de especialidades para las que se presta servicio'],
        ];

        // Inserta los datos si no existen, o los actualiza si ya existen
        DB::table('configuracion')->upsert(
            $filas,
            ['clave'],
            ['valor','descripcion']
        );
    }
}
