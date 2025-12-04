<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RolesSeeder;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function test_administrador_puede_listar_usuarios()
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/usuarios');

        $response
            ->assertStatus(200)
            ->assertJsonFragment([
                'email' => $otroUsuario->email,
            ]);
    }

    #[Test]
    public function test_usuario_no_admin_no_puede_listar_usuarios()
    {
        $user = User::factory()->create();
        $user->assignRole('usuario');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/usuarios');

        // Spatie debe lanzar UnauthorizedException 403
        $response->assertStatus(403);
    }
}
