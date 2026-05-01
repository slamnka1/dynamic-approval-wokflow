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

        foreach (['ali', 'rahim'] as $name) {
            User::updateOrCreate(
                ['email' => "{$name}@cyberagora.test"],
                [
                    'name' => ucfirst($name).' Requester',
                    'password' => 'password',
                    'role' => UserRole::Requester,
                ],
            );
        }

        foreach (['omar', 'sarra', 'salma'] as $name) {
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
