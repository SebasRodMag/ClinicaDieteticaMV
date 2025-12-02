<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Database\Seeders\ConfiguracionSeeder;
use Carbon\Carbon;

class NuevaCitaTest extends TestCase
{
    use RefreshDatabase;

    protected User $userPaciente;
    protected User $userEspecialista;
    protected Paciente $paciente;
    protected Especialista $especialista;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'paciente']);
        Role::firstOrCreate(['name' => 'especialista']);

        // Configuración necesaria para validaciones de horarios, festivos, etc.
        $this->seed(ConfiguracionSeeder::class);

        $this->userPaciente = User::factory()->create();
        $this->userPaciente->assignRole('paciente');
        $this->paciente = Paciente::factory()->create([
            'user_id' => $this->userPaciente->id,
        ]);

        $this->userEspecialista = User::factory()->create();
        $this->userEspecialista->assignRole('especialista');
        $this->especialista = Especialista::factory()->create([
            'user_id' => $this->userEspecialista->id,
        ]);
    }

    private function fechaLaboralValida(): string
    {
        $fecha = Carbon::now()->addDays(2)->setTime(10, 0);

        // Evitar fin de semana
        while ($fecha->isWeekend()) {
            $fecha->addDay();
        }

        return $fecha->format('Y-m-d H:i:s');
    }

    /** @test */
    public function crea_cita_con_todos_los_parametros_proporcionados()
    {
        $this->actingAs($this->userPaciente, 'sanctum');

        $response = $this->postJson('/api/citas', [
            'id_paciente'     => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'fecha_hora_cita' => $this->fechaLaboralValida(),
            'tipo_cita'       => 'presencial',
            'comentario'      => 'Test cita',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
            'tipo_cita'       => 'presencial',
        ]);
    }

    /** @test */
    public function crea_cita_cuando_no_se_envia_id_paciente_y_lo_deduce_del_usuario_logueado()
    {
        $this->actingAs($this->userPaciente, 'sanctum');

        $response = $this->postJson('/api/citas', [
            // 'id_paciente' omitido a propósito
            'id_especialista' => $this->especialista->id,
            'fecha_hora_cita' => $this->fechaLaboralValida(),
            'tipo_cita'       => 'presencial',
            'comentario'      => 'Test cita sin id_paciente',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
        ]);
    }

    /** @test */
    public function crea_cita_cuando_no_se_envia_id_especialista_y_lo_deduce_del_usuario_logueado()
    {
        $this->actingAs($this->userEspecialista, 'sanctum');

        $response = $this->postJson('/api/citas', [
            'id_paciente'     => $this->paciente->id,
            // 'id_especialista' omitido a propósito
            'fecha_hora_cita' => $this->fechaLaboralValida(),
            'tipo_cita'       => 'presencial',
            'comentario'      => 'Test cita sin id_especialista',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $this->paciente->id,
            'id_especialista' => $this->especialista->id,
        ]);
    }
}
