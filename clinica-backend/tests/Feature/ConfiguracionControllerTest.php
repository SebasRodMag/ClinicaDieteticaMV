<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Support\Facades\Schema;

class ConfiguracionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function devuelve_configuraciones_formateadas_correctamente()
    {
        $this->seed(ConfiguracionSeeder::class);

        $response = $this->getJson('/api/configuracion');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Configuraciones cargadas correctamente'])
            ->assertJsonStructure([
                'message',
                'configuraciones' => [
                    'duracion_cita',
                    'precio_cita',
                    'dias_no_laborables',
                    'horario_laboral' => ['apertura', 'cierre', 'jornada_partida'],
                    'notificaciones_email',
                    'color_tema',
                    'Crear_cita_paciente',
                    'Especialidades'
                ]
            ]);

        $config = $response->json('configuraciones');
        $this->assertEquals(30, $config['duracion_cita']);
        $this->assertEquals('#28a745', $config['color_tema']);
        $this->assertIsArray($config['Especialidades']);
        $this->assertContains('Nutrición', $config['Especialidades']);
    }

    /** @test */
    public function devuelve_error_si_falla_la_consulta()
    {
        Schema::drop('configuracion');

        $response = $this->getJson('/api/configuracion');

        $response->assertStatus(500)
            ->assertJsonStructure([
                'message',
                'detalle',
            ]);
    }
}
