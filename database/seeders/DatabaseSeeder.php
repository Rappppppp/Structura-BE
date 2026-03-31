<?php

namespace Database\Seeders;

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
        // Seed users with various roles
        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
        ]);
    }
}
