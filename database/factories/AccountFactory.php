<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
        $currency = fake()->randomElement($currencies);
        
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->randomElement([
                'Checking Account',
                'Savings Account',
                '401(k)',
                'Roth IRA',
                'Investment Account',
                'Emergency Fund',
            ]) . ' - ' . fake()->company(),
            'currency' => $currency,
            'conversion_rate' => $currency === 'USD' ? 1.0000 : fake()->randomFloat(4, 0.5, 2.0),
            'retirement_type' => fake()->randomElement(\App\Enums\RetirementType::cases()),
        ];
    }
}
