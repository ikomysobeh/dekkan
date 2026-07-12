<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_token_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'cashier@shop.test',
            'password' => 'password', // hashed by the model cast
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'cashier@shop.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'cashier@shop.test']);

        $this->postJson('/api/login', [
            'email'    => 'cashier@shop.test',
            'password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_protected_route_requires_a_token(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJson(['id' => $user->id, 'email' => $user->email]);
    }
}
