<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CambiarEstadoCitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }

    /** @test */
    public function especialista_puede_cambiar_estado_de_su_cita()
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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->subHour(), // ya pasada da igual para el test
            'estado'          => 'pendiente',
            'tipo_cita'       => 'presencial',
        ]);

        $response = $this->actingAs($userEspecialista, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cambiar-estado", [
                'estado' => 'realizada',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'message'       => 'Estado de la cita actualizado correctamente.',
                'estado_nuevo'  => 'realizada',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'realizada',
        ]);
    }

    /** @test */
    public function administrador_puede_cambiar_estado_de_cualquier_cita()
    {
        $userAdmin = User::factory()->create();
        $userAdmin->assignRole('administrador');

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
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'fecha_hora_cita' => now()->addDay(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'presencial',
        ]);

        $response = $this->actingAs($userAdmin, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cambiar-estado", [
                'estado' => 'realizada',
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'estado_anterior' => 'pendiente',
                'estado_nuevo'    => 'realizada',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'realizada',
        ]);
    }

    /** @test */
    public function paciente_no_puede_cambiar_estado_a_realizada()
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
            'fecha_hora_cita' => now()->addDay(),
            'estado'          => 'pendiente',
            'tipo_cita'       => 'presencial',
        ]);

        $response = $this->actingAs($userPaciente, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cambiar-estado", [
                'estado' => 'realizada',
            ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No autorizado para cambiar el estado de esta cita.',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'pendiente',
        ]);
    }
}
