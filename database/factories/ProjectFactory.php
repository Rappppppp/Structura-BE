<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Project',
            'description' => fake()->paragraph(),
            'budget' => fake()->numberBetween(50000, 500000),
            'progress' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['active', 'completed', 'on-hold']),
            'deadline_at' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ];
    }

    /**
     * Create an active project.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'progress' => fake()->numberBetween(10, 80),
        ]);
    }

    /**
     * Create a completed project.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    /**
     * Create a project on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on-hold',
            'progress' => fake()->numberBetween(5, 30),
        ]);
    }
}
