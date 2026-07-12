<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actor = User::factory()->create();
        Sanctum::actingAs($this->actor);
    }

    public function test_store_creates_a_user_with_hashed_password(): void
    {
        $this->postJson('/api/users', [
            'name'     => 'New Staff',
            'email'    => 'staff@shop.test',
            'password' => 'secret123',
        ])->assertStatus(201)->assertJson(['success' => true, 'data' => ['email' => 'staff@shop.test']]);

        $user = User::where('email', 'staff@shop.test')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    /** B2: updating a user without changing the email must not fail validation. */
    public function test_update_with_same_email_succeeds(): void
    {
        $user = User::factory()->create(['email' => 'keep@shop.test', 'name' => 'Old Name']);

        $this->putJson("/api/users/{$user->id}", [
            'name'  => 'New Name',
            'email' => 'keep@shop.test', // unchanged
        ])->assertOk();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'email' => 'keep@shop.test']);
    }

    public function test_cannot_delete_own_account(): void
    {
        $this->deleteJson("/api/users/{$this->actor->id}")
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('users', ['id' => $this->actor->id]);
    }
}
