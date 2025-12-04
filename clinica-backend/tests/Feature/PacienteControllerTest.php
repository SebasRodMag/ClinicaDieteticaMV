<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Database\Seeders\RolesSeeder;

class PacienteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);


        $this->usuario = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        $this->usuario->assignRole('paciente');

        $this->paciente = Paciente::factory()->create(['user_id' => $this->usuario->id]);
    }

    public function test_actualizar_paciente_con_password_incorrecta()
    {
        $data = [
            'nombre'           => 'NuevoNombre',
            'apellidos'        => 'NuevoApellido',
            'dni_usuario'      => $this->usuario->dni_usuario,
            'email'            => $this->usuario->email,
            'direccion'        => 'Nueva direccion',
            'fecha_nacimiento' => '1990-01-01',
            'telefono'         => '123456789',
            'password_actual'  => 'contraseña_incorrecta',
        ];

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->putJson("/api/pacientes/{$this->paciente->id}", $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['error' => 'Contraseña actual incorrecta']);
    }

    public function test_actualizar_paciente_exitoso()
    {
        $data = [
            'nombre'           => 'NuevoNombre',
            'apellidos'        => 'NuevoApellido',
            'dni_usuario'      => $this->usuario->dni_usuario,
            'email'            => $this->usuario->email,
            'direccion'        => 'Nueva direccion',
            'fecha_nacimiento' => '1990-01-01',
            'telefono'         => '123456789',
            'password_actual'  => 'password',
        ];

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->putJson("/api/pacientes/{$this->paciente->id}", $data);

        $response->assertStatus(200);

        $this->assertEquals('NuevoNombre', $this->usuario->fresh()->nombre);
        $this->assertEquals('NuevoApellido', $this->usuario->fresh()->apellidos);
    }

    public function test_cambiar_password_exitoso()
    {
        $data = [
            'password_actual'            => 'password',
            'password_nuevo'             => 'nuevo1234',
            'password_nuevo_confirmation'=> 'nuevo1234',
        ];

        $response = $this->actingAs($this->usuario, 'sanctum')
            ->putJson("/api/pacientes/{$this->paciente->id}/cambiar-password", $data);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('nuevo1234', $this->usuario->fresh()->password));
    }
}
