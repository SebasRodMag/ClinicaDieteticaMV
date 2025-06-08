<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class NuevaCitaTest extends TestCase
{
    use RefreshDatabase;

    protected $userPaciente;
    protected $userEspecialista;
    protected $paciente;
    protected $especialista;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if not exist
        Role::firstOrCreate(['name' => 'paciente']);
        Role::firstOrCreate(['name' => 'especialista']);

        // Create a user with paciente role and related Paciente
        $this->userPaciente = User::factory()->create();
        $this->userPaciente->assignRole('paciente');
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->userPaciente->id,
        ]);

        // Create a user with especialista role and related Especialista
        $this->userEspecialista = User::factory()->create();
        $this->userEspecialista->assignRole('especialista');
        $this->especialista = Especialista::factory()->create([
            'user_id' => $this->userEspecialista->id,
        ]);
    }

    /** @test */
    public function it_creates_cita_with_all_parameters_provided()
    {
        $this->actingAs($this->userPaciente);

        $response = $this->postJson('/api/citas', [
            'paciente_id' => $this->paciente->id,
            'especialista_id' => $this->especialista->id,
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
            'comentarios' => 'Test cita',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'cita']);
        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'tipo_cita' => 'presencial',
        ]);
    }

    /** @test */
    public function it_creates_cita_when_paciente_id_is_missing()
    {
        $this->actingAs($this->userPaciente);

        $response = $this->postJson('/api/citas', [
            'especialista_id' => $this->especialista->id,
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
            'comentarios' => 'Test cita sin paciente_id',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'cita']);
        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
        ]);
    }

    /** @test */
    public function it_creates_cita_when_especialista_id_is_missing()
    {
        $this->actingAs($this->userEspecialista);

        $response = $this->postJson('/api/citas', [
            'paciente_id' => $this->paciente->id,
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
            'comentarios' => 'Test cita sin especialista_id',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'cita']);
        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
        ]);
    }

    /** @test */
    public function it_creates_cita_when_both_ids_are_missing()
    {
        $this->actingAs($this->userPaciente);

        $response = $this->postJson('/api/citas', [
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
            'comentarios' => 'Test cita sin ambos ids',
        ]);

        // Since especialista_id is missing and userPaciente is not especialista, expect 404
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_if_no_paciente_found_for_user()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');
        $this->actingAs($user);

        $response = $this->postJson('/api/citas', [
            'especialista_id' => $this->especialista->id,
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Paciente no encontrado para el usuario autenticado']);
    }

    /** @test */
    public function it_returns_404_if_no_especialista_found_for_user()
    {
        $user = User::factory()->create();
        $user->assignRole('especialista');
        $this->actingAs($user);

        $response = $this->postJson('/api/citas', [
            'paciente_id' => $this->paciente->id,
            'fecha_hora_cita' => now()->addDay()->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Especialista no encontrado para el usuario autenticado']);
    }
}
