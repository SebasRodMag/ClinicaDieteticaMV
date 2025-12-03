<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolesSeeder;

class HistorialControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function paciente_puede_ver_sus_historiales()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        Paciente::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/mis-historiales');

        $response->assertStatus(200);
    }

    #[Test]
    public function especialista_puede_listar_historiales_de_pacientes()
    {
        $user = User::factory()->create();
        $user->assignRole('especialista');

        Especialista::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/historial-paciente');

        $response->assertStatus(200);
    }

    #[Test]
    public function usuario_sin_rol_apropiado_no_puede_ver_historiales()
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/mis-historiales');

        $response->assertStatus(403);
    }
}
