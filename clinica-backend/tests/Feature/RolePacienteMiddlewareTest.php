<?php

namespace Tests\Feature\Middleware;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePacienteMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_con_rol_paciente_puede_acceder()
    {
        $usuario = User::factory()->create(['rol' => 'paciente']);
        $paciente = Paciente::factory()->create(['user_id' => $usuario->id]);

        $response = $this->actingAs($usuario)->getJson("/api/pacientes/{$paciente->id}/citas");

        $response->assertStatus(200); // Asumiendo que hay lógica implementada
    }

    public function test_usuario_con_rol_no_autorizado_es_rechazado()
    {
        $usuario = User::factory()->create(['rol' => 'admin']); // o 'especialista'
        $paciente = Paciente::factory()->create();

        $response = $this->actingAs($usuario)->getJson("/api/pacientes/{$paciente->id}/citas");

        $response->assertStatus(403);
    }

    public function test_usuario_no_autenticado_redireccionado_o_rechazado()
    {
        $paciente = Paciente::factory()->create();

        $response = $this->getJson("/api/pacientes/{$paciente->id}/citas");

        $response->assertStatus(401); // No autenticado
    }
}
