<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Especialista;
use App\Models\Cita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CancelaCitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles para el guard web (sin especificar 'api')
        Role::create(['name' => 'paciente']);
        Role::create(['name' => 'especialista']);
        Role::create(['name' => 'administrador']);
    }

    /** @test */
    public function paciente_puede_cancelar_su_cita()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');
        $paciente = Paciente::factory()->create(['user_id' => $user->id]);
        $especialista = Especialista::factory()->create();
        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => $cita->id]))
            ->assertStatus(200)
            ->assertJson(['message' => 'Cita cancelada correctamente']);

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
        ]);
    }

    /** @test */
    public function especialista_puede_cancelar_su_cita()
    {
        $user = User::factory()->create();
        $user->assignRole('especialista');
        $especialista = Especialista::factory()->create(['user_id' => $user->id]);
        $paciente = Paciente::factory()->create();
        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => $cita->id]))
            ->assertStatus(200)
            ->assertJson(['message' => 'Cita cancelada correctamente']);

        $this->assertDatabaseHas('citas', [
            'id' => $cita->id,
            'estado' => 'cancelada',
        ]);
    }

    /** @test */
    public function usuario_no_autorizado_no_puede_cancelar_cita()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');
        $paciente = Paciente::factory()->create();
        $especialista = Especialista::factory()->create();
        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'estado' => 'pendiente',
        ]);

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => $cita->id]))
            ->assertStatus(403)
            ->assertJson(['message' => 'No autorizado: no es su cita']);
    }

    /** @test */
    public function no_se_puede_cancelar_cita_ya_cancelada_o_realizada()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');
        $paciente = Paciente::factory()->create(['user_id' => $user->id]);
        $especialista = Especialista::factory()->create();
        $cita = Cita::factory()->create([
            'id_paciente' => $paciente->id,
            'id_especialista' => $especialista->id,
            'estado' => 'cancelada',
        ]);

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => $cita->id]))
            ->assertStatus(400)
            ->assertJson(['message' => 'La cita ya no se puede cancelar']);
    }

    /** @test */
    public function cancelar_cita_con_id_invalido_devuelve_error()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => -1]))
            ->assertStatus(400)
            ->assertJson(['message' => 'ID de cita inválido']);
    }

    /** @test */
    public function cancelar_cita_no_encontrada_devuelve_error()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $this->actingAs($user)
            ->postJson(route('citas.cancelar', ['id' => 9999]))
            ->assertStatus(404)
            ->assertJson(['message' => 'Cita no encontrada']);
    }
}
