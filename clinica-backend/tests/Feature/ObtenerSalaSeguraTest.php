<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
class ObtenerSalaSeguraTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function test_paciente_puede_obtener_sala_de_su_cita_telematica()
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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addHour(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'telemática',
            'nombre_sala'     => 'clinicaDietetica-cita-'.$this->faker->numberBetween(1, 999),
        ]);

        $response = $this->actingAs($userPaciente, 'sanctum')
            ->getJson("/api/citas/{$cita->id_cita}/sala-segura");

        $response
            ->assertStatus(200)
            ->assertJson([
                'nombre_sala' => $cita->nombre_sala,
            ]);
    }

    #[Test]
    public function test_especialista_puede_obtener_sala_de_su_cita_telematica()
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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addHour(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'telemática',
            'nombre_sala'     => 'clinicaDietetica-cita-'.$this->faker->numberBetween(1, 999),
        ]);

        $response = $this->actingAs($userEspecialista, 'sanctum')
            ->getJson("/api/citas/{$cita->id_cita}/sala-segura");

        $response
            ->assertStatus(200)
            ->assertJson([
                'nombre_sala' => $cita->nombre_sala,
            ]);
    }

    #[Test]
    public function test_usuario_no_participante_no_puede_obtener_sala()
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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addHour(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'telemática',
            'nombre_sala'     => 'clinicaDietetica-cita-'.$this->faker->numberBetween(1, 999),
        ]);

        $intruso = User::factory()->create();
        $intruso->assignRole('paciente'); // pero no es el paciente de la cita

        $response = $this->actingAs($intruso, 'sanctum')
            ->getJson("/api/citas/{$cita->id_cita}/sala-segura");

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No tienes permisos para acceder a esta sala',
            ]);
    }

    #[Test]
    public function test_cita_no_telematica_o_sin_sala_devuelve_error_400()
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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addHour(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'presencial', // ¡No telemática!
            'nombre_sala'     => null,
        ]);

        $response = $this->actingAs($userPaciente, 'sanctum')
            ->getJson("/api/citas/{$cita->id_cita}/sala-segura");

        $response
            ->assertStatus(400)
            ->assertJson([
                'message' => 'La cita no es telemática o no tiene sala asignada',
            ]);
    }
}
