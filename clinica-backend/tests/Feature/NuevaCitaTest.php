<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Configuracion;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NuevaCitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);

        // Configuración mínima para que pasen las validaciones de horario
        Configuracion::create([
            'clave' => 'horario_laboral',
            'valor' => json_encode(['apertura' => '08:00', 'cierre' => '14:00']),
        ]);

        Configuracion::create([
            'clave' => 'duracion_cita',
            'valor' => '30',
        ]);

        Configuracion::create([
            'clave' => 'dias_no_laborables',
            'valor' => json_encode([]),
        ]);
    }

    /** @test */
    public function crea_cita_con_todos_los_parametros_proporcionados()
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $paciente = Paciente::factory()->create();
        $especialista = Especialista::factory()->create();

        $fecha = Carbon::now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/citas', [
                'paciente_id'     => $paciente->id,
                'especialista_id' => $especialista->id,
                'fecha_hora_cita' => $fecha,
                'tipo_cita'       => 'presencial',
                'comentario'      => 'Test cita',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
            'tipo_cita'       => 'presencial',
            'estado'          => 'pendiente',
        ]);
    }

    /** @test */
    public function crea_cita_cuando_no_se_envia_id_paciente_y_lo_deduce_del_usuario_logueado()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $paciente = Paciente::factory()->create([
            'user_id' => $user->id,
        ]);

        // Especialista cualquiera (lo pasamos por ID)
        $especialista = Especialista::factory()->create();

        $fecha = Carbon::now()->addDays(2)->setTime(10, 30)->format('Y-m-d H:i:s');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/citas', [
                // sin 'paciente_id'
                'especialista_id' => $especialista->id,
                'fecha_hora_cita' => $fecha,
                'tipo_cita'       => 'presencial',
                'comentario'      => 'Test sin id_paciente',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
        ]);
    }

    /** @test */
    public function crea_cita_cuando_no_se_envia_id_especialista_y_lo_deduce_del_usuario_logueado()
    {
        $user = User::factory()->create();
        $user->assignRole('especialista');

        $especialista = Especialista::factory()->create([
            'user_id' => $user->id,
        ]);

        $paciente = Paciente::factory()->create();

        $fecha = Carbon::now()->addDays(2)->setTime(11, 0)->format('Y-m-d H:i:s');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/citas', [
                'paciente_id'     => $paciente->id,
                // sin 'especialista_id'
                'fecha_hora_cita' => $fecha,
                'tipo_cita'       => 'presencial',
                'comentario'      => 'Test sin id_especialista',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'cita']);

        $this->assertDatabaseHas('citas', [
            'id_paciente'     => $paciente->id,
            'id_especialista' => $especialista->id,
        ]);
    }
}
