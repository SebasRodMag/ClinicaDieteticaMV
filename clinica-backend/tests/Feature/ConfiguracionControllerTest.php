<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\ConfiguracionSeeder;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Database\Seeders\RolesSeeder;

class ConfiguracionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ConfiguracionSeeder::class);
        $this->seed(RolesSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('administrador');
    }

    #[Test]
    public function test_devuelve_configuraciones_formateadas_correctamente()
    {
        $this->seed(ConfiguracionSeeder::class);

        $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/obtenerConfiguraciones');

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

    #[Test]
    public function test_devuelve_error_si_falla_la_consulta()
    {
        Schema::drop('configuracion');

        $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/obtenerConfiguraciones');

        $response->assertStatus(500)
            ->assertJsonStructure([
                'message',
                'detalle',
            ]);
    }

    #[Test]
    public function test_administrador_puede_actualizar_una_configuracion_por_clave()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/cambiarConfiguraciones/color_tema', [
                'valor' => '#123456',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Configuración actualizada correctamente',
            ]);

        $this->assertDatabaseHas('configuracion', [
            'clave' => 'color_tema',
            'valor' => '#123456',
        ]);
    }

    #[Test]
    public function test_usuario_no_administrador_no_puede_actualizar_configuracion()
    {
        $userNoAdmin = User::factory()->create();
        $userNoAdmin->assignRole('paciente');

        $response = $this->actingAs($userNoAdmin, 'sanctum')
            ->putJson('/api/cambiarConfiguraciones/color_tema', [
                'valor' => '#999999',
            ]);

        $response->assertStatus(403);
    }
}
