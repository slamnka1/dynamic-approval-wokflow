<?php

namespace Tests;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function makeUser(UserRole $role, array $attrs = []): User
    {
        $defaults = [
            'name' => match ($role) {
                UserRole::Admin => 'Test Admin',
                UserRole::Approver => 'Test Approver',
                UserRole::Requester => 'Test Requester',
            },
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => $role,
        ];

        return User::create(array_merge($defaults, $attrs));
    }

    protected function actingAsRole(UserRole $role, array $attrs = []): User
    {
        $user = $this->makeUser($role, $attrs);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }
}
