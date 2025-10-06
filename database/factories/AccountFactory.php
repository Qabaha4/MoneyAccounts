<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'currency_id' => Currency::factory(),
            'name' => $this->faker->words(2, true) . ' Account',
            'type' => $this->faker->randomElement(['checking', 'savings', 'credit', 'investment']),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'initial_balance' => $this->faker->randomFloat(2, 0, 5000),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a checking account.
     */
    public function checking(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'checking',
            'name' => $this->faker->words(2, true) . ' Checking',
        ]);
    }

    /**
     * Create a savings account.
     */
    public function savings(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'savings',
            'name' => $this->faker->words(2, true) . ' Savings',
        ]);
    }

    /**
     * Create a credit account.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit',
            'name' => $this->faker->words(2, true) . ' Credit',
            'balance' => $this->faker->randomFloat(2, -5000, 0),
        ]);
    }

    /**
     * Create an investment account.
     */
    public function investment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'investment',
            'name' => $this->faker->words(2, true) . ' Investment',
        ]);
    }

    /**
     * Create an account with a specific balance.
     */
    public function withBalance(float $balance): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $balance,
            'initial_balance' => $balance,
        ]);
    }

    /**
     * Create an account with zero balance.
     */
    public function zeroBalance(): static
    {
        return $this->withBalance(0);
    }
}