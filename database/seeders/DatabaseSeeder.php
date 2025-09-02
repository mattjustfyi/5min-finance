<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\GoalAllocation;
use App\Models\DebtAllocation;
use App\Models\Snapshot;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 users with 2-5 accounts each, plus allocations and snapshots
        User::factory(10)
            ->has(Account::factory()->count(rand(2, 5)))
            ->has(GoalAllocation::factory()->count(rand(1, 3)), 'goalAllocations')
            ->has(DebtAllocation::factory()->count(rand(1, 2)), 'debtAllocations')
            ->has(Snapshot::factory()->count(rand(2, 8)), 'snapshots')
            ->create();

        // Create a specific test user with known credentials
        // LOGIN: email@matt.fyi / password
        $testUser = User::factory()->create([
            'name' => 'Matt',
            'email' => 'email@matt.fyi',
            'password' => bcrypt('password'), // Easy to remember: "password"
        ]);

        // Give the test user 3 specific cash accounts
        Account::create([
            'user_id' => $testUser->id,
            'name' => 'Primary Checking',
            'currency' => 'USD',
            'conversion_rate' => 1.0000,
            'retirement_type' => \App\Enums\RetirementType::NON_RETIREMENT,
        ]);
        
        Account::create([
            'user_id' => $testUser->id,
            'name' => 'Savings Account',
            'currency' => 'USD',
            'conversion_rate' => 1.0000,
            'retirement_type' => \App\Enums\RetirementType::NON_RETIREMENT,
        ]);
        
        Account::create([
            'user_id' => $testUser->id,
            'name' => 'Emergency Fund',
            'currency' => 'USD',
            'conversion_rate' => 1.0000,
            'retirement_type' => \App\Enums\RetirementType::NON_RETIREMENT,
        ]);

        // Give the test user some specific allocations
        GoalAllocation::factory()->count(2)->create([
            'user_id' => $testUser->id,
        ]);
        
        DebtAllocation::factory()->count(1)->create([
            'user_id' => $testUser->id,
        ]);

        // Create specific snapshots for the test user
        
        // Snapshot 1: With goals, debts, and accounts (inserted first, so older date)
        $snapshot1 = Snapshot::create([
            'user_id' => $testUser->id,
            'date' => now()->subDays(35)->format('Y-m-d'),
            'notes' => 'Test snapshot with all data',
            'accounts' => [],
            'goals' => [],
            'debts' => [],
        ]);

        // Get the user's data to populate snapshot 1
        $userAccounts = Account::where('user_id', $testUser->id)->get();
        $userGoals = GoalAllocation::where('user_id', $testUser->id)->get();
        $userDebts = DebtAllocation::where('user_id', $testUser->id)->get();

        // Populate accounts data
        $accountsData = [];
        foreach ($userAccounts as $account) {
            $accountsData[$account->id] = [
                'balance' => rand(1000, 50000) / 100, // Random balance
            ];
        }

        // Populate goals data
        $goalsData = [];
        foreach ($userGoals as $goal) {
            $goalsData[] = [
                'goalId' => $goal->id,
                'targetAmount' => rand(5000, 50000) / 100,
                'deductions' => [
                    [
                        'amount' => rand(100, 1000) / 100,
                        'description' => 'Sample deduction',
                        'dateOccurred' => now()->subDays(15)->format('Y-m-d'),
                    ],
                ],
            ];
        }

        // Populate debts data
        $debtsData = [];
        foreach ($userDebts as $debt) {
            $debtsData[] = [
                'debtId' => $debt->id,
                'amount' => rand(500, 5000) / 100,
            ];
        }

        // Update snapshot 1 with all data
        $snapshot1->update([
            'accounts' => $accountsData,
            'goals' => $goalsData,
            'debts' => $debtsData,
        ]);

        // Snapshot 2: Empty snapshot (inserted second, so newer date)
        Snapshot::create([
            'user_id' => $testUser->id,
            'date' => now()->subDays(20)->format('Y-m-d'),
            'notes' => 'Test snapshot with no data',
            'accounts' => [],
            'goals' => [],
            'debts' => [],
        ]);

        // Give the test user some additional snapshots with all accounts and unique dates
        $additionalDates = [
            now()->subDays(10)->format('Y-m-d'),
            now()->subDays(5)->format('Y-m-d'),
            now()->format('Y-m-d'),
        ];
        
        foreach ($additionalDates as $date) {
            $accountsData = [];
            foreach ($userAccounts as $account) {
                $accountsData[$account->id] = [
                    'balance' => rand(1000, 50000) / 100,
                ];
            }
            
            Snapshot::create([
                'user_id' => $testUser->id,
                'date' => $date,
                'notes' => 'Additional test snapshot',
                'accounts' => $accountsData,
                'goals' => [],
                'debts' => [],
            ]);
        }
    }
}
