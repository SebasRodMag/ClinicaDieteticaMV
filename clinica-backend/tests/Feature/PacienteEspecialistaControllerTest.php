<?php

namespace Tests\Feature;

use App\Models\Especialista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspecialistaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_especialidades()
    {
        Especialista::factory()->create(['especialidad' => 'Nutrición']);
        Especialista::factory()->create(['especialidad' => 'Cardiología']);
        Especialista::factory()->create(['especialidad' => 'Nutrición']);

        $response = $this->getJson('/api/especialidades');

        $response->assertStatus(200);
        $response->assertJsonFragment(['Nutrición']);
        $response->assertJsonFragment(['Cardiología']);
        $response->assertJsonCount(2);
    }

    public function test_listar_especialistas_por_especialidad()
    {
        Especialista::factory()->create(['especialidad' => 'Nutrición']);
        Especialista::factory()->create(['especialidad' => 'Cardiología']);

        $response = $this->getJson('/api/especialistas?especialidad=Nutrición');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json);
        $this->assertEquals('Nutrición', $json[0]['especialidad']);
    }

    public function test_listar_especialistas_sin_parametro_especialidad_error()
    {
        $response = $this->getJson('/api/especialistas');

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Se requiere el parámetro especialidad']);
    }
}
