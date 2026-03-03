<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Available roles for users.
     */
    protected static array $roles = ['user', 'admin', 'project_manager', 'architect', 'engineer'];

    /**
     * Available companies for realistic data.
     */
    protected static array $companies = [
        'TechCorp Solutions',
        'Digital Innovations Inc',
        'Enterprise Systems Ltd',
        'Cloud First Technologies',
        'Data Driven Analytics',
        'Smart Solutions Group',
        'NextGen Development',
        'Integrated Systems Co',
        'Advanced Tech Partners',
        'Strategic IT Consulting',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(self::$roles),
            'company' => fake()->randomElement(self::$companies),
            'phone_number' => $this->generatePhoneNumber(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'email' => 'admin@structura.local',
            'name' => 'Administrator',
        ]);
    }

    /**
     * Create a project manager user.
     */
    public function projectManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'project_manager',
        ]);
    }

    /**
     * Create an architect user.
     */
    public function architect(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'architect',
        ]);
    }

    /**
     * Create an engineer user.
     */
    public function engineer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'engineer',
        ]);
    }

    /**
     * Generate a valid international phone number.
     */
    protected function generatePhoneNumber(): string
    {
        $formats = [
            '+1' . fake()->bothify('###-###-####'),
            '+44' . fake()->numerify('##########'),
            '+33' . fake()->numerify('##########'),
            '+49' . fake()->numerify('##########'),
        ];

        return fake()->randomElement($formats);
    }
}
