<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CancelarCitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        Notification::fake(); // Para evitar enviar emails reales
    }

    private function crearPacienteConUsuario(): array
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $paciente = Paciente::factory()->create([
            'user_id' => $user->id,
        ]);

        return [$user, $paciente];
    }

    private function crearEspecialistaConUsuario(): array
    {
        $user = User::factory()->create();
        $user->assignRole('especialista');

        $especialista = Especialista::factory()->create([
            'user_id' => $user->id,
        ]);

        return [$user, $especialista];
    }

    private function crearCitaPendiente(Paciente $paciente, Especialista $especialista): Cita
    {
        return Cita::factory()->create([
            'id_paciente'    => $paciente->id,
            'id_especialista'=> $especialista->id,
            'estado'         => 'pendiente',
        ]);
    }

    #[Test]
    public function test_paciente_puede_cancelar_su_cita()
    {
        [$userPaciente, $paciente] = $this->crearPacienteConUsuario();
        [$userEsp, $especialista] = $this->crearEspecialistaConUsuario();

        $cita = $this->crearCitaPendiente($paciente, $especialista);

        $this->actingAs($userPaciente, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cancelar")
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Cita cancelada correctamente',
                'id_cita' => $cita->id_cita,
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'cancelada',
        ]);
    }

    #[Test]
    public function test_especialista_puede_cancelar_su_cita()
    {
        [$userPaciente, $paciente] = $this->crearPacienteConUsuario();
        [$userEsp, $especialista] = $this->crearEspecialistaConUsuario();

        $cita = $this->crearCitaPendiente($paciente, $especialista);

        $this->actingAs($userEsp, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cancelar")
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Cita cancelada correctamente',
                'id_cita' => $cita->id_cita,
            ]);
    }

    #[Test]
    public function test_usuario_no_autorizado_no_puede_cancelar_cita()
    {
        // Cita entre paciente y especialista
        [$userPaciente, $paciente] = $this->crearPacienteConUsuario();
        [$userEsp, $especialista] = $this->crearEspecialistaConUsuario();
        $cita = $this->crearCitaPendiente($paciente, $especialista);

        // paciente B, sin relación con la cita
        [$userOtro, $pacienteOtro] = $this->crearPacienteConUsuario();

        $this->actingAs($userOtro, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cancelar")
            ->assertStatus(403)
            ->assertJson([
                'message' => 'No autorizado para cancelar esta cita',
            ]);
    }

    #[Test]
    public function test_no_se_puede_cancelar_cita_ya_cancelada_o_realizada()
    {
        [$userPaciente, $paciente] = $this->crearPacienteConUsuario();
        [$userEsp, $especialista] = $this->crearEspecialistaConUsuario();

        $cita = Cita::factory()->create([
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'estado'          => 'realizada',
        ]);

        $this->actingAs($userPaciente, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cancelar")
            ->assertStatus(400)
            ->assertJson([
                'message' => 'La cita no se puede cancelar en su estado actual',
            ]);
    }

    #[Test]
    public function test_cancelar_cita_con_id_invalido_devuelve_error()
    {
        [$userPaciente, $paciente] = $this->crearPacienteConUsuario();

        // ID que seguro no existe
        $this->actingAs($userPaciente, 'sanctum')
            ->patchJson("/api/citas/-1/cancelar")
            ->assertStatus(404)
            ->assertJson([
                'message' => 'Cita no encontrada',
            ]);
    }
}
