<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Snapshot>
 */
class SnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'notes' => $this->faker->optional()->sentence(),
            'accounts' => [
                'account123' => [
                    'balance' => $this->faker->randomFloat(2, 1000, 50000),
                ],
                'account456' => [
                    'balance' => $this->faker->randomFloat(2, 500, 25000),
                ],
            ],
            'goals' => [
                [
                    'goalId' => 'goal123',
                    'targetAmount' => $this->faker->randomFloat(2, 5000, 50000),
                    'deductions' => [
                        [
                            'amount' => $this->faker->randomFloat(2, 100, 1000),
                            'description' => $this->faker->sentence(3),
                            'dateOccurred' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                        ],
                    ],
                ],
                [
                    'goalId' => 'goal456',
                    'targetAmount' => $this->faker->randomFloat(2, 2000, 20000),
                    'deductions' => [],
                ],
            ],
            'debts' => [
                [
                    'debtId' => 'debt789',
                    'amount' => $this->faker->randomFloat(2, 500, 5000),
                ],
                [
                    'debtId' => 'debt101',
                    'amount' => $this->faker->randomFloat(2, 200, 3000),
                ],
            ],
        ];
    }
}
