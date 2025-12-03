<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolesSeeder;

class DocumentoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function invitado_no_puede_listar_documentos()
    {
        $response = $this->getJson('/api/documentos');

        $response->assertStatus(401);
    }

    #[Test]
    public function paciente_autenticado_puede_listar_sus_documentos()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        Paciente::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/mis-documentos');

        $response->assertStatus(200);
    }

    public function especialista_autenticado_puede_listar_documentos_generales()
    {
        // Crear usuario especialista
        $user = User::factory()->create();
        $user->assignRole('especialista');

        Especialista::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/documentos');

        // El backend devuelve un 404 si no hay documentos con un mensaje específico
        $response
            ->assertStatus(404)
            ->assertJson([
                'message' => 'No hay documentos disponibles',
            ]);
    }

}
