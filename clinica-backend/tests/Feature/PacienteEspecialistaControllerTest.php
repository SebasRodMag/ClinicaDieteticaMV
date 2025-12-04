<?php

namespace Tests\Feature;

use App\Models\Especialista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\RolesSeeder;
class PacienteEspecialistaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        $this->user = User::factory()->create();
        // le damos un rol cualquiera que tenga acceso a esos endpoints; por ejemplo paciente
        $this->user->assignRole('paciente');
    }

    public function test_listar_especialidades()
    {
        Especialista::factory()->create(['especialidad' => 'Nutrición']);
        Especialista::factory()->create(['especialidad' => 'Cardiología']);
        Especialista::factory()->create(['especialidad' => 'Nutrición']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/especialidades');

        $response->assertStatus(200);
        $json = $response->json();

        // Deben existir solo 2 especialidades distintas
        $this->assertCount(2, $json);
        $this->assertContains('Nutrición', $json);
        $this->assertContains('Cardiología', $json);
    }

    public function test_listar_especialistas_por_especialidad()
    {
        $espNutri = Especialista::factory()->create(['especialidad' => 'Nutrición']);
        $espCardio = Especialista::factory()->create(['especialidad' => 'Cardiología']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/especialistas?especialidad=Nutrición');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(1, $json);
        $this->assertEquals($espNutri->id, $json[0]['id']);
    }

    public function test_listar_especialistas_sin_parametro_especialidad_error()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/especialistas');

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Se requiere el parámetro especialidad']);
    }
}
