<?php

namespace Database\Seeders;

use App\Domain\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@cyberagora.test'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => UserRole::Admin,
            ],
        );

        foreach (['alice', 'bob'] as $name) {
            User::updateOrCreate(
                ['email' => "{$name}@cyberagora.test"],
                [
                    'name' => ucfirst($name).' Requester',
                    'password' => 'password',
                    'role' => UserRole::Requester,
                ],
            );
        }

        foreach (['carol', 'dave', 'erin'] as $name) {
            User::updateOrCreate(
                ['email' => "{$name}@cyberagora.test"],
                [
                    'name' => ucfirst($name).' Approver',
                    'password' => 'password',
                    'role' => UserRole::Approver,
                ],
            );
        }
    }
}
