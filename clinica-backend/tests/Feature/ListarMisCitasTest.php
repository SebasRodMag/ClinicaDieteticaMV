<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListarMisCitasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }

    /** @test */
    public function paciente_con_citas_recibe_listado_correcto()
    {
        $userPaciente = User::factory()->create();
        $userPaciente->assignRole('paciente');

        $paciente = Paciente::factory()->create([
            'user_id' => $userPaciente->id,
        ]);

        $userEspecialista = User::factory()->create();
        $userEspecialista->assignRole('especialista');

        $especialista = Especialista::factory()->create([
            'user_id' => $userEspecialista->id,
        ]);

        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addDay()->setTime(10, 0),
            'estado' => 'pendiente',
            'tipo_cita' => 'presencial',
        ]);

        $response = $this->actingAs($userPaciente, 'sanctum')
            ->getJson('/api/listar-citas-paciente');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'citas' => [
                    [
                        'id',
                        'fecha',
                        'hora',
                        'estado',
                        'tipo_cita',
                    ]
                ]
            ]);

        $json = $response->json();
        $this->assertCount(1, $json['citas']);
        $this->assertEquals($cita->id_cita, $json['citas'][0]['id']);
    }

    /** @test */
    public function paciente_sin_registro_en_tabla_pacientes_recibe_mensaje_adecuado()
    {
        $userPaciente = User::factory()->create();
        $userPaciente->assignRole('paciente');

        $response = $this->actingAs($userPaciente, 'sanctum')
            ->getJson('/api/listar-citas-paciente');

        $response
            ->assertStatus(200)
            ->assertJson([
                'citas' => [],
                'message' => 'Este usuario aún no está vinculado como paciente.',
            ]);
    }

    /** @test */
    public function especialista_con_citas_recibe_listado_correcto()
    {
        $userEspecialista = User::factory()->create();
        $userEspecialista->assignRole('especialista');

        $especialista = Especialista::factory()->create([
            'user_id' => $userEspecialista->id,
        ]);

        $userPaciente = User::factory()->create();
        $userPaciente->assignRole('paciente');

        $paciente = Paciente::factory()->create([
            'user_id' => $userPaciente->id,
        ]);

        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addDay()->setTime(11, 0),
            'estado' => 'pendiente',
            'tipo_cita' => 'presencial',
        ]);

        $response = $this->actingAs($userEspecialista, 'sanctum')
            ->getJson('/api/listar-citas-paciente');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'citas' => [
                    [
                        'id',
                        'fecha',
                        'hora',
                        'estado',
                        'tipo_cita',
                    ]
                ]
            ]);

        $json = $response->json();
        $this->assertCount(1, $json['citas']);
        $this->assertEquals($cita->id_cita, $json['citas'][0]['id']);
    }

    /** @test */
    public function usuario_sin_rol_paciente_ni_especialista_no_puede_listar_citas()
    {
        $user = User::factory()->create();
        $user->assignRole('usuario'); // rol básico

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/listar-citas-paciente');

        $response
            ->assertStatus(403)
            ->assertJsonFragment([
                'message' => 'User does not have the right roles.',
            ]);
    }
}
