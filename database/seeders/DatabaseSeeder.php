<?php

namespace Database\Seeders;

use App\Support\FormCache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DemoUsersSeeder::class,
            DemoFormsSeeder::class,
        ]);

        app(FormCache::class)->flushAll();
    }
}
