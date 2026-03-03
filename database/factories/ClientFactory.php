<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Available industries.
     */
    protected static array $industries = [
        'Technology',
        'Finance',
        'Healthcare',
        'Manufacturing',
        'Retail',
        'Real Estate',
        'Telecommunications',
        'Energy',
        'Transportation',
        'Education',
        'Hospitality',
        'Media & Entertainment',
    ];

    /**
     * Available client statuses.
     */
    protected static array $statuses = ['active', 'review', 'completed', 'on-hold'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => fake()->randomElement(self::$industries),
            'contact_person' => fake()->name(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => $this->generatePhoneNumber(),
            'location' => fake()->city() . ', ' . fake()->state(),
            'active_projects' => fake()->numberBetween(0, 5),
            'total_value' => fake()->numberBetween(10000, 500000),
            'status' => fake()->randomElement(self::$statuses),
            'account_owner_id' => User::factory(),
        ];
    }

    /**
     * Create an active high-value client.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'total_value' => fake()->numberBetween(100000, 500000),
            'active_projects' => fake()->numberBetween(2, 5),
        ]);
    }

    /**
     * Create a completed client project.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'active_projects' => 0,
        ]);
    }

    /**
     * Create a client in review status.
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'review',
        ]);
    }

    /**
     * Create a client on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on-hold',
        ]);
    }

    /**
     * Create a tech industry client.
     */
    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Technology',
        ]);
    }

    /**
     * Create a finance industry client.
     */
    public function finance(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Finance',
        ]);
    }

    /**
     * Create a healthcare industry client.
     */
    public function healthcare(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Healthcare',
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
