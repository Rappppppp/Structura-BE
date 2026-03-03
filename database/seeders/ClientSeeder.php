<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get project managers to assign as account owners
        $accountOwners = User::byRole('project_manager')->pluck('id')->toArray();

        // Create high-value active clients
        Client::factory(5)->highValue()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        // Create standard active clients
        Client::factory(10)->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        // Create completed clients
        Client::factory(3)->completed()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        // Create clients in review
        Client::factory(2)->inReview()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        // Create clients on hold
        Client::factory(2)->onHold()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        // Create industry-specific clients for better testing data
        Client::factory(3)->tech()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        Client::factory(2)->finance()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);

        Client::factory(2)->healthcare()->create([
            'account_owner_id' => fn () => fake()->randomElement($accountOwners),
        ]);
    }
}
