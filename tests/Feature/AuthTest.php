<?php

namespace Tests\Feature;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_token(): void
    {
        $resp = $this->postJson('/api/auth/register', [
            'name' => 'Ali',
            'email' => 'ali@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $resp->assertCreated();
        $resp->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
        $this->assertDatabaseHas('users', ['email' => 'ali@example.test', 'role' => UserRole::Requester->value]);
    }

    public function test_register_can_create_admin_when_role_provided(): void
    {
        $resp = $this->postJson('/api/auth/register', [
            'name' => 'A', 'email' => 'a@x.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $resp->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'a@x.test', 'role' => UserRole::Admin->value]);
    }

    public function test_register_validates_password_confirmation(): void
    {
        $resp = $this->postJson('/api/auth/register', [
            'name' => 'A', 'email' => 'a@x.test',
            'password' => 'password123', 'password_confirmation' => 'different',
        ]);

        $resp->assertUnprocessable();
    }

    public function test_register_validates_unique_email(): void
    {
        $this->makeUser(UserRole::Requester, ['email' => 'dup@x.test']);

        $resp = $this->postJson('/api/auth/register', [
            'name' => 'A', 'email' => 'dup@x.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $resp->assertUnprocessable();
    }

    public function test_login_returns_token_on_correct_credentials(): void
    {
        User::create([
            'name' => 'A', 'email' => 'a@x.test',
            'password' => 'secret-pw', 'role' => UserRole::Requester,
        ]);

        $resp = $this->postJson('/api/auth/login', ['email' => 'a@x.test', 'password' => 'secret-pw']);
        $resp->assertOk();
        $resp->assertJsonStructure(['user', 'token']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.test', 'password' => 'right', 'role' => UserRole::Requester]);

        $this->postJson('/api/auth/login', ['email' => 'a@x.test', 'password' => 'wrong'])
            ->assertUnprocessable();
    }

    public function test_me_returns_current_user_when_authed(): void
    {
        $user = $this->actingAsRole(UserRole::Requester);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_me_is_unauthenticated_without_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $this->actingAsRole(UserRole::Requester);

        $this->postJson('/api/auth/logout')->assertOk();
    }
}
