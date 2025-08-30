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

        // Give the test user some specific accounts
        Account::factory()->count(3)->create([
            'user_id' => $testUser->id,
        ]);

        // Give the test user some specific allocations
        GoalAllocation::factory()->count(2)->create([
            'user_id' => $testUser->id,
        ]);
        
        DebtAllocation::factory()->count(1)->create([
            'user_id' => $testUser->id,
        ]);

        // Give the test user some snapshots
        Snapshot::factory()->count(5)->create([
            'user_id' => $testUser->id,
        ]);
    }
}
