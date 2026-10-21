<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_inicia_sesion_y_obtiene_un_token(): void
    {
        $user = User::factory()->admin()->create();

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.rol', 'admin')
            ->json('token');

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_cierra_sesion_revocando_el_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('spa')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
