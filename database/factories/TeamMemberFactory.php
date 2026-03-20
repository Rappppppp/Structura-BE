<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['Developer', 'Architect', 'Project Lead', 'Manager']),
            'projects_count' => fake()->numberBetween(1, 5),
        ];
    }

    /**
     * Create a team member with developer role.
     */
    public function developer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Developer',
        ]);
    }

    /**
     * Create a team member with architect role.
     */
    public function architect(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Architect',
        ]);
    }

    /**
     * Create a team member with project lead role.
     */
    public function projectLead(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Project Lead',
        ]);
    }

    /**
     * Create a team member with manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'Manager',
        ]);
    }
}
