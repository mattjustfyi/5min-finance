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
            'accounts' => [],
            'goals' => [],
            'debts' => [],
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Snapshot $snapshot) {
            // Get the user's accounts, goals, and debts
            $accounts = \App\Models\Account::where('user_id', $snapshot->user_id)->get();
            $goals = \App\Models\GoalAllocation::where('user_id', $snapshot->user_id)->get();
            $debts = \App\Models\DebtAllocation::where('user_id', $snapshot->user_id)->get();

            // Create accounts data using real IDs
            $accountsData = [];
            foreach ($accounts->take(rand(1, $accounts->count())) as $account) {
                $accountsData[$account->id] = [
                    'balance' => $this->faker->randomFloat(2, 1000, 50000),
                ];
            }

            // Create goals data using real IDs
            $goalsData = [];
            foreach ($goals->take(rand(0, $goals->count())) as $goal) {
                $goalsData[] = [
                    'goalId' => $goal->id,
                    'targetAmount' => $this->faker->randomFloat(2, 5000, 50000),
                    'deductions' => rand(0, 1) ? [
                        [
                            'amount' => $this->faker->randomFloat(2, 100, 1000),
                            'description' => $this->faker->sentence(3),
                            'dateOccurred' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                        ],
                    ] : [],
                ];
            }

            // Create debts data using real IDs
            $debtsData = [];
            foreach ($debts->take(rand(0, $debts->count())) as $debt) {
                $debtsData[] = [
                    'debtId' => $debt->id,
                    'amount' => $this->faker->randomFloat(2, 500, 5000),
                ];
            }

            // Update the snapshot with the real data
            $snapshot->update([
                'accounts' => $accountsData,
                'goals' => $goalsData,
                'debts' => $debtsData,
            ]);
        });
    }
}
