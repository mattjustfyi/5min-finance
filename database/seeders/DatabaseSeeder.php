<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 users with 2-5 accounts each
        User::factory(10)
            ->has(Account::factory()->count(rand(2, 5)))
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
    }
}
