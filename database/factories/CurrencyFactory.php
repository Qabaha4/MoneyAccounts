<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->currencyCode(),
            'name' => $this->faker->randomElement([
                'US Dollar',
                'Euro',
                'British Pound',
                'Japanese Yen',
                'Canadian Dollar',
                'Australian Dollar',
                'Swiss Franc',
                'Chinese Yuan',
            ]),
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', 'C$', 'A$', 'CHF', '¥']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a USD currency.
     */
    public function usd(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
        ]);
    }

    /**
     * Create a EUR currency.
     */
    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
        ]);
    }
}