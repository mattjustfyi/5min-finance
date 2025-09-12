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
        // Create 10 users with 2-5 accounts each, plus allocations (snapshots created manually later)
        $users = User::factory(10)
            ->has(Account::factory()->count(rand(2, 5)))
            ->has(GoalAllocation::factory()->count(rand(1, 3)), 'goalAllocations')
            ->has(DebtAllocation::factory()->count(rand(1, 2)), 'debtAllocations')
            ->create();

        // Create realistic snapshots for each user
        foreach ($users as $user) {
            $this->createRealisticSnapshots($user, rand(6, 10));
        }

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

        // Create realistic snapshots for the test user
        $this->createRealisticSnapshots($testUser, 8);
    }

    /**
     * Create realistic snapshots for a user with consistent 4-week intervals
     */
    private function createRealisticSnapshots(User $user, int $snapshotCount = 8): void
    {
        // Get the user's data once
        $userAccounts = Account::where('user_id', $user->id)->get();
        $userGoals = GoalAllocation::where('user_id', $user->id)->get();
        $userDebts = DebtAllocation::where('user_id', $user->id)->get();

        // Set consistent goal targets (4-digit round numbers)
        $goalTargets = [];
        foreach ($userGoals as $goal) {
            $goalTargets[$goal->id] = collect([5000, 7500, 10000, 12000, 15000, 20000])->random();
        }

        // Create snapshots going back in 4-week intervals
        for ($i = 0; $i < $snapshotCount; $i++) {
            $daysBack = $i * 28; // 4-week intervals
            $date = now()->subDays($daysBack)->format('Y-m-d');
            
            // Create realistic account balances that evolve over time
            $accountsData = [];
            foreach ($userAccounts as $account) {
                // Base balance with some variation over time
                $baseBalance = rand(5000, 50000) / 100;
                $variation = sin($i * 0.5) * ($baseBalance * 0.2); // Realistic fluctuation
                $balance = max(100, $baseBalance + $variation); // Never go below $1
                
                $accountsData[$account->id] = [
                    'balance' => round($balance, 2),
                ];
            }

            // Create realistic goals data with consistent targets
            $goalsData = [];
            
            // Add 1-2 deductions total per snapshot (not per goal)
            $totalDeductions = rand(0, 2);
            $deductionsAdded = 0;
            
            foreach ($userGoals as $goal) {
                $deductions = [];
                
                // Only add deductions if we haven't reached the limit and there's a chance
                if ($deductionsAdded < $totalDeductions && rand(1, 100) <= 60) {
                    $deductions[] = [
                        'amount' => rand(50, 300),
                        'description' => collect(['Car repair', 'Medical expense', 'Home maintenance', 'Emergency purchase'])->random(),
                        'dateOccurred' => now()->subDays($daysBack + rand(0, 27))->format('Y-m-d'),
                    ];
                    $deductionsAdded++;
                }
                
                $goalsData[] = [
                    'goalId' => $goal->id,
                    'targetAmount' => $goalTargets[$goal->id],
                    'deductions' => $deductions,
                ];
            }

            // Create realistic debts that generally decrease over time
            $debtsData = [];
            foreach ($userDebts as $debt) {
                $baseAmount = rand(2000, 10000) / 100;
                // Debts should generally decrease over time (payments made)
                $reduction = $i * 50; // $50 reduction per snapshot period
                $amount = max(0, $baseAmount - $reduction);
                
                $debtsData[] = [
                    'debtId' => $debt->id,
                    'amount' => round($amount, 2),
                ];
            }

            // Create the snapshot
            Snapshot::create([
                'user_id' => $user->id,
                'date' => $date,
                'notes' => $i === 0 ? 'Latest snapshot' : "Snapshot from {$date}",
                'accounts' => $accountsData,
                'goals' => $goalsData,
                'debts' => $debtsData,
            ]);
        }
    }
}
