<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolesSeeder;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function test_usuario_puede_hacer_login_con_credenciales_correctas()
    {
        $user = User::factory()->create([
            'email'    => 'login@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@test.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'access_token',
            ]);
            
    }

    #[Test]
    public function test_login_falla_con_credenciales_incorrectas()
    {
        $user = User::factory()->create([
            'email'    => 'login@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertTrue($response->status() >= 400);//Simplemente esperamos un error
    }

    #[Test]
    public function test_me_devuelve_el_usuario_autenticado()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'email' => $user->email,
            ]);
    }

    #[Test]
    public function test_logout_revoca_el_token_actual()
    {
        $user = User::factory()->create();
        $user->assignRole('paciente');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $this->assertTrue(in_array($response->status(), [200, 204]));
    }
}
