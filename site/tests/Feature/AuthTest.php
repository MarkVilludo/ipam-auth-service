<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for auth API: register, login, and me (authenticated user).
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'user', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
    }

    /** Register: creates user, assigns default role, returns JWT and user data. */
    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.user.name', 'New User');
        $response->assertJsonPath('data.user.email', 'newuser@example.com');
        $response->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($user->hasRole('user'));
    }

    /** Register: returns 422 when name, email, or password are missing or invalid. */
    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /** Register: returns 422 when email is already taken. */
    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** Login: returns 200 and JWT + user when email/password are correct. */
    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
        ]);
        $user->assignRole('user');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.user.email', 'login@example.com');
        $response->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'role']]]);
    }

    /** Login: returns 401 when password is wrong. */
    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'user@example.com']);
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
    }

    /** Me: returns current user when request has valid JWT (using token from login). */
    public function test_me_returns_authenticated_user_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);
        $user->assignRole('user');

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'me@example.com',
            'password' => 'password',
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'me@example.com');
    }

    /** Me: returns 401 when no Authorization header is sent. */
    public function test_me_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }
}
