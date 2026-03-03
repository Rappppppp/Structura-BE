<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->admin()->create();

        // Create project managers
        User::factory(3)->projectManager()->create();

        // Create architects
        User::factory(2)->architect()->create();

        // Create engineers
        User::factory(5)->engineer()->create();

        // Create regular users
        User::factory(5)->create();
    }
}
