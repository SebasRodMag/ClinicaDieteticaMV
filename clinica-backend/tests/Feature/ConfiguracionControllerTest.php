<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ConfiguracionSeeder;

class ConfiguracionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function devuelve_configuraciones_formateadas_correctamente()
    {
        // Ejecutamos el seeder real
        $this->seed(ConfiguracionSeeder::class);

        $response = $this->getJson('/api/configuracion');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Configuraciones cargadas correctamente',
                'configuraciones' => [
                    'duracion_cita' => 30,
                    'precio_cita' => 25.00,
                    'dias_no_laborables' => ['2025-12-25', '2025-01-01', '2025-05-01'],
                    'horario_laboral' => [
                        'apertura' => '08:00',
                        'cierre' => '16:00',
                        'jornada_partida' => false,
                    ],
                    'notificaciones_email' => false,
                    'color_tema' => '#28a745',
                    'Crear_cita_paciente' => true,
                    'Especialidades' => [
                        'Endocrinología',
                        'Nutrición',
                        'Psicología',
                        'Psiquiatría',
                        'Reumatología',
                        'Pediatría',
                        'Medicina general',
                    ],
                ],
            ]);
    }

    /** @test */
    public function devuelve_error_si_falla_la_consulta()
    {
        \Schema::drop('Configuracion');

        $response = $this->getJson('/api/configuracion');

        $response->assertStatus(500)
            ->assertJsonStructure([
                'error',
                'detalle',
            ]);
    }
}
