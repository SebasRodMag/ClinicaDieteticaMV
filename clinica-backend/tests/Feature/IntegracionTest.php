<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Especialista;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Configuracion;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    /**
     * Configuración mínima para que nuevaCita() no falle:
     * - horario_laboral
     * - duracion_cita
     * - dias_no_laborables
     */
    protected function seedConfiguracionBasica(): void
    {
        Configuracion::create([
            'clave' => 'horario_laboral',
            'valor' => json_encode([
                'apertura' => '08:00',
                'cierre' => '14:00',
            ]),
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

    /**
     * Flujo completo:
     * - Paciente crea cita con especialista
     * - Especialista cancela la cita
     * - La cita queda en estado 'cancelada'
     */
    public function test_flujo_completo_crear_y_cancelar_cita(): void
    {
        $this->seedConfiguracionBasica();

        // Paciente
        $userPaciente = User::factory()->create();
        $userPaciente->assignRole('paciente');
        $paciente = Paciente::factory()->create([
            'user_id' => $userPaciente->id,
        ]);

        // Especialista
        $userEspecialista = User::factory()->create();
        $userEspecialista->assignRole('especialista');
        $especialista = Especialista::factory()->create([
            'user_id' => $userEspecialista->id,
        ]);

        // Paciente crea cita (POST /api/citas)
        $this->actingAs($userPaciente, 'sanctum')
            ->postJson('/api/citas', [
                'paciente_id' => $paciente->id,
                'especialista_id' => $especialista->id,
                'fecha_hora_cita' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
                'tipo_cita' => 'presencial',
            ])
            ->assertStatus(201);

        $cita = Cita::first();
        $this->assertNotNull($cita);

        // Especialista cancela la cita (PATCH /api/citas/{id}/cancelar)
        $this->actingAs($userEspecialista, 'sanctum')
            ->patchJson("/api/citas/{$cita->id_cita}/cancelar")
            ->assertStatus(200);

        $this->assertEquals('cancelada', $cita->fresh()->estado);
    }

    /**
     * Confirmar que cambiar el rol afecta a los endpoints protegidos por middleware de rol.
     *
     * - Un usuario con rol paciente puede acceder a /api/mis-historiales
     * - Tras cambiar su rol a especialista, deja de poder acceder a ese endpoint
     */
    public function test_cambiar_rol_afecta_permisos_en_endpoints_de_paciente(): void
    {
        $this->seed(RolesSeeder::class);

        // Se crea un usuario y luego se asigna el rol paciente
        $user = User::factory()->create();
        $user->assignRole('paciente');
        $paciente = Paciente::factory()->create([
            'user_id' => $user->id,
        ]);

        // Admin que hará el cambio de rol
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        // Como paciente, puede acceder a /api/mis-historiales (middleware role:paciente)
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mis-historiales')
            ->assertStatus(200);

        // Admin cambia su rol a especialista (PUT /api/usuariosbaja/{id})
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/usuariosbaja/{$user->id}", [
                'rol' => 'especialista',
            ])
            ->assertStatus(200);

        // Recargar el usuario desde BD para que no conserve el rol 'paciente' en memoria
        $user->refresh();

        // Ahora el mismo usuario ya no puede acceder a /api/mis-historiales (middleware role:paciente)
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/mis-historiales')
            ->assertStatus(403);
    }

    /**
     * Usuario solicita sala segura para una cita telemática donde es participante.
     * Cubre:
     * - Cita telemática con nombre_sala
     * - Endpoint /api/citas/{id}/sala-segura
     */
    public function test_generar_sala_segura_devuelve_nombre_sala_valido(): void
    {
        $this->seedConfiguracionBasica();

        // Paciente
        $userPaciente = User::factory()->create();
        $userPaciente->assignRole('paciente');
        $paciente = Paciente::factory()->create([
            'user_id' => $userPaciente->id,
        ]);

        // Especialista
        $userEspecialista = User::factory()->create();
        $userEspecialista->assignRole('especialista');
        $especialista = Especialista::factory()->create([
            'user_id' => $userEspecialista->id,
        ]);

        // Paciente crea una cita telemática
        $this->actingAs($userPaciente, 'sanctum')
            ->postJson('/api/citas', [
                'paciente_id' => $paciente->id,
                'especialista_id' => $especialista->id,
                'fecha_hora_cita' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
                'tipo_cita' => 'telemática',
            ])
            ->assertStatus(201);

        $cita = Cita::first();
        $this->assertNotNull($cita);
        $this->assertEquals('telemática', $cita->tipo_cita);
        $this->assertNotEmpty($cita->nombre_sala); // la generó nuevaCita()

        // El paciente pide la sala segura
        $response = $this->actingAs($userPaciente, 'sanctum')
            ->getJson("/api/citas/{$cita->id_cita}/sala-segura");

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['nombre_sala']);
    }

}
