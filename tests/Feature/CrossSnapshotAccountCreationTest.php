<?php

use App\Models\Account;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\AccountService;
use App\Services\SnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    $this->accountService = app(AccountService::class);
    $this->snapshotService = app(SnapshotService::class);
});

it('creates account and adds to current snapshot only', function () {
    // Create current snapshot
    $currentSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    DB::transaction(function () use ($currentSnapshot) {
        // Create account
        $account = $this->accountService->createAccount([
            'name' => 'Test Account',
            'currency' => 'USD',
            'conversion_rate' => 1.0,
        ], $this->user->id);
        
        // Add to snapshots
        $snapshotsToUpdate = collect([$currentSnapshot]);
        $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshotsToUpdate);
    });
    
    // Verify account was created
    $account = Account::where('name', 'Test Account')->first();
    expect($account)->not->toBeNull();
    expect($account->currency)->toBe('USD');
    
    // Verify snapshot was updated
    $currentSnapshot->refresh();
    expect($currentSnapshot->accounts)->toHaveKey($account->id);
    expect($currentSnapshot->accounts[$account->id]['balance'])->toBe(0);
});

it('creates account and adds to current and newer snapshots', function () {
    // Create snapshots
    $currentSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
    ]);
    
    $newerSnapshot1 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    $newerSnapshot2 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [],
    ]);
    
    DB::transaction(function () use ($currentSnapshot) {
        // Create account
        $account = $this->accountService->createAccount([
            'name' => 'Test Account',
            'currency' => 'USD',
            'conversion_rate' => 1.0,
        ], $this->user->id);
        
        // Get all snapshots to update (current + newer)
        $snapshotsToUpdate = collect([$currentSnapshot]);
        $newerSnapshots = $this->snapshotService->getAllNewerSnapshots(
            $this->user->id,
            $currentSnapshot->date
        );
        $snapshotsToUpdate = $snapshotsToUpdate->merge($newerSnapshots);
        
        // Add account to all snapshots
        $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshotsToUpdate);
    });
    
    // Verify account was created
    $account = Account::where('name', 'Test Account')->first();
    expect($account)->not->toBeNull();
    
    // Verify all snapshots were updated
    $currentSnapshot->refresh();
    $newerSnapshot1->refresh();
    $newerSnapshot2->refresh();
    
    expect($currentSnapshot->accounts)->toHaveKey($account->id);
    expect($newerSnapshot1->accounts)->toHaveKey($account->id);
    expect($newerSnapshot2->accounts)->toHaveKey($account->id);
    
    // All should have balance 0
    expect($currentSnapshot->accounts[$account->id]['balance'])->toBe(0);
    expect($newerSnapshot1->accounts[$account->id]['balance'])->toBe(0);
    expect($newerSnapshot2->accounts[$account->id]['balance'])->toBe(0);
});

it('creates account and adds to date range including older snapshots', function () {
    // Create snapshots across a date range
    $olderSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(3),
        'accounts' => [],
    ]);
    
    $middleSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
    ]);
    
    $currentSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    $newerSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [],
    ]);
    
    DB::transaction(function () use ($currentSnapshot, $olderSnapshot) {
        // Create account
        $account = $this->accountService->createAccount([
            'name' => 'Test Account',
            'currency' => 'USD',
            'conversion_rate' => 1.0,
        ], $this->user->id);
        
        // Get all snapshots to update
        $snapshotsToUpdate = collect([$currentSnapshot]);
        
        // Add newer snapshots
        $newerSnapshots = $this->snapshotService->getAllNewerSnapshots(
            $this->user->id,
            $currentSnapshot->date
        );
        $snapshotsToUpdate = $snapshotsToUpdate->merge($newerSnapshots);
        
        // Add older snapshots in date range
        $olderSnapshots = $this->snapshotService->getSnapshotsInDateRange(
            $this->user->id,
            $olderSnapshot->date, // From older snapshot date
            $currentSnapshot->date->copy()->subDay() // To day before current
        );
        $snapshotsToUpdate = $snapshotsToUpdate->merge($olderSnapshots);
        
        // Add account to all snapshots
        $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshotsToUpdate);
    });
    
    // Verify account was created
    $account = Account::where('name', 'Test Account')->first();
    expect($account)->not->toBeNull();
    
    // Verify all relevant snapshots were updated
    $olderSnapshot->refresh();
    $middleSnapshot->refresh();
    $currentSnapshot->refresh();
    $newerSnapshot->refresh();
    
    expect($olderSnapshot->accounts)->toHaveKey($account->id);
    expect($middleSnapshot->accounts)->toHaveKey($account->id);
    expect($currentSnapshot->accounts)->toHaveKey($account->id);
    expect($newerSnapshot->accounts)->toHaveKey($account->id);
});

it('validates that account does not already exist in target snapshots', function () {
    // Create account first
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Existing Account',
    ]);
    
    // Create snapshot that already has this account
    $snapshotWithAccount = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [$account->id => ['balance' => 100.00]],
    ]);
    
    $snapshotsToUpdate = collect([$snapshotWithAccount]);
    
    // Validation should fail
    $isValid = $this->snapshotService->validateAccountNotInSnapshots($account->id, $snapshotsToUpdate);
    expect($isValid)->toBeFalse();
});

it('handles transaction rollback on failure', function () {
    // Create snapshots
    $snapshot1 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
    ]);
    
    $snapshot2 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    try {
        DB::transaction(function () use ($snapshot1, $snapshot2) {
            // Create account
            $account = $this->accountService->createAccount([
                'name' => 'Test Account',
                'currency' => 'USD',
                'conversion_rate' => 1.0,
            ], $this->user->id);
            
            // Modify snapshot2 to have this account (simulating a race condition)
            $snapshot2->update(['accounts' => [$account->id => ['balance' => 50.00]]]);
            
            // Try to add account to both snapshots - should fail validation
            $snapshotsToUpdate = collect([$snapshot1, $snapshot2]);
            
            if (!$this->snapshotService->validateAccountNotInSnapshots($account->id, $snapshotsToUpdate)) {
                throw new \InvalidArgumentException('Account already exists in target snapshots');
            }
            
            $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshotsToUpdate);
        });
        
        // Should not reach here
        expect(false)->toBeTrue('Transaction should have been rolled back');
        
    } catch (\InvalidArgumentException $e) {
        // Expected exception
        expect($e->getMessage())->toContain('Account already exists');
        
        // Verify entire transaction was rolled back - no account should exist
        $account = Account::where('name', 'Test Account')->first();
        expect($account)->toBeNull();
        
        // Verify snapshot1 was not updated 
        $snapshot1->refresh();
        expect($snapshot1->accounts)->toBeEmpty();
    }
});

it('handles performance limit for too many snapshots', function () {
    // This test would be slow with real snapshots, so we'll just test the service directly
    $snapshots = collect();
    
    // Create a collection that exceeds the limit
    for ($i = 0; $i < 101; $i++) {
        $snapshots->push(new Snapshot(['id' => $i]));
    }
    
    $account = Account::factory()->create(['user_id' => $this->user->id]);
    
    expect(fn() => $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshots))
        ->toThrow(\InvalidArgumentException::class, 'Cannot update more than 100 snapshots');
});

it('handles empty date range gracefully', function () {
    $currentSnapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    // Try to get snapshots in a range where none exist
    $emptyRange = $this->snapshotService->getSnapshotsInDateRange(
        $this->user->id,
        now()->subDays(10),
        now()->subDays(5)
    );
    
    expect($emptyRange)->toBeEmpty();
});

it('handles duplicate account detection correctly', function () {
    $account = Account::factory()->create(['user_id' => $this->user->id]);
    
    // Create snapshot with the account
    $snapshotWithAccount = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [$account->id => ['balance' => 100]],
    ]);
    
    // Create snapshot without the account
    $snapshotWithoutAccount = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [],
    ]);
    
    $snapshots = collect([$snapshotWithAccount, $snapshotWithoutAccount]);
    
    // Should detect duplicate and return false
    $isValid = $this->snapshotService->validateAccountNotInSnapshots($account->id, $snapshots);
    expect($isValid)->toBeFalse();
});

it('handles account creation with validation error', function () {
    expect(function () {
        $this->accountService->createAccount([
            'name' => '', // Invalid name
            'currency' => 'USD',
            'conversion_rate' => 1.0,
        ], $this->user->id);
    })->toThrow(\Illuminate\Validation\ValidationException::class);
    
    // Verify no account was created
    $account = Account::where('currency', 'USD')->where('user_id', $this->user->id)->first();
    expect($account)->toBeNull();
});

it('handles race condition detection with double-check in service', function () {
    $account = Account::factory()->create(['user_id' => $this->user->id]);
    
    $snapshot1 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
    ]);
    
    $snapshot2 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [$account->id => ['balance' => 50]], // Account already exists
    ]);
    
    $snapshots = collect([$snapshot1, $snapshot2]);
    
    // Validation should fail because account exists in snapshot2
    expect($this->snapshotService->validateAccountNotInSnapshots($account->id, $snapshots))->toBeFalse();
    
    // And the addAccountToMultipleSnapshots should also catch the duplicate
    expect(fn() => $this->snapshotService->addAccountToMultipleSnapshots($account->id, $snapshots))
        ->toThrow(\InvalidArgumentException::class, 'already exists in snapshot');
});

it('correctly handles users with no snapshots', function () {
    $newUser = User::factory()->create();
    
    // User with no snapshots
    $olderSnapshots = $this->snapshotService->getSnapshotsInDateRange(
        $newUser->id,
        now()->subDays(10),
        now()->subDays(1)
    );
    
    $newerSnapshots = $this->snapshotService->getAllNewerSnapshots(
        $newUser->id,
        now()
    );
    
    expect($olderSnapshots)->toBeEmpty();
    expect($newerSnapshots)->toBeEmpty();
});