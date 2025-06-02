<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CitaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Paciente $paciente;
    private Especialista $especialista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        $this->paciente = Paciente::factory()->create(['user_id' => $this->usuario->id]);
        $this->especialista = Especialista::factory()->create();
    }

    public function test_listar_citas_paciente_autorizado()
    {
        Cita::factory()->count(3)->create(['id_paciente' => $this->paciente->id, 'id_especialista' => $this->especialista->id]);

        $response = $this->actingAs($this->usuario)->getJson("/api/pacientes/{$this->paciente->id}/citas");

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_listar_citas_paciente_no_autorizado()
    {
        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($otroUsuario)->getJson("/api/pacientes/{$this->paciente->id}/citas");

        $response->assertStatus(403);
    }

    public function test_crear_cita_valida()
    {
        $fechaHora = Carbon::now()->addDay()->setHour(10)->setMinute(0)->format('Y-m-d H:i:s');

        $data = [
            'id_especialista' => $this->especialista->id,
            'fecha_hora_cita' => $fechaHora,
            'tipo_cita' => 'presencial',
            'comentario' => 'Consulta general',
            'es_primera' => true,
        ];

        $response = $this->actingAs($this->usuario)->postJson("/api/pacientes/{$this->paciente->id}/citas", $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'fecha_hora_cita' => $fechaHora,
            'estado' => 'pendiente',
        ]);
    }

    public function test_crear_cita_en_fin_de_semana_falla()
    {
        // Busca el próximo sábado
        $sabado = Carbon::now()->next(Carbon::SATURDAY)->setHour(10)->setMinute(0);

        $data = [
            'id_especialista' => $this->especialista->id,
            'fecha_hora_cita' => $sabado->format('Y-m-d H:i:s'),
            'tipo_cita' => 'presencial',
            'comentario' => '',
            'es_primera' => false,
        ];

        $response = $this->actingAs($this->usuario)->postJson("/api/pacientes/{$this->paciente->id}/citas", $data);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'La fecha es fin de semana o festivo']);
    }

    public function test_cancelar_cita_exitosa()
    {
        $cita = Cita::factory()->create([
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($this->usuario)->patchJson("/api/citas/{$cita->id}/cancelar");

        $response->assertStatus(200);
        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelado',
        ]);
    }

    public function test_cancelar_cita_no_autorizado()
    {
        $cita = Cita::factory()->create([
            'id_paciente' => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'estado' => 'pendiente',
        ]);

        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($otroUsuario)->patchJson("/api/citas/{$cita->id}/cancelar");

        $response->assertStatus(403);
    }
}
