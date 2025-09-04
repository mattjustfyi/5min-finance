<?php

use App\Models\Account;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Resources\Snapshots\Pages\ViewAccountLists;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account1 = Account::factory()->create([
        'user_id' => $this->user->id, 
        'name' => 'Test Account 1',
        'retirement_type' => 'non_retirement'
    ]);
    $this->account2 = Account::factory()->create([
        'user_id' => $this->user->id, 
        'name' => 'Test Account 2',
        'retirement_type' => 'non_retirement'
    ]);
    
    $this->snapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [
            $this->account1->id => ['balance' => 100.00],
            $this->account2->id => ['balance' => 200.00],
        ],
        'goals' => [],
        'debts' => [],
    ]);
});

it('can delete an account from snapshot and soft delete account', function () {
    $this->actingAs($this->user);
    
    // Verify initial state
    expect($this->snapshot->accounts)->toHaveKey($this->account1->id);
    expect($this->account1->trashed())->toBeFalse();
    
    // Delete the account
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Verify account removed from snapshot
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->not->toHaveKey($this->account1->id);
    expect($this->snapshot->accounts)->toHaveKey($this->account2->id);
    
    // Verify account soft deleted
    $this->account1->refresh();
    expect($this->account1->trashed())->toBeTrue();
});

it('updates account balances state after deletion', function () {
    $this->actingAs($this->user);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Verify initial balance state
    expect($component->get('accountBalances'))->toHaveKey($this->account1->id);
    expect($component->get('accountBalances'))->toHaveKey($this->account2->id);
    
    // Delete account
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Verify balance state updated
    expect($component->get('accountBalances'))->not->toHaveKey($this->account1->id);
    expect($component->get('accountBalances'))->toHaveKey($this->account2->id);
});

it('only allows user to delete their own accounts', function () {
    $otherUser = User::factory()->create();
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);
    
    // Add other user's account to snapshot (shouldn't be possible normally but testing edge case)
    $this->snapshot->update([
        'accounts' => [
            $this->account1->id => ['balance' => 100.00],
            $otherAccount->id => ['balance' => 300.00],
        ]
    ]);
    
    $this->actingAs($this->user);
    
    // Try to delete other user's account
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->call('deleteAccount', $otherAccount->id, $otherAccount->name);
    
    // Other user's account should not be soft deleted
    $otherAccount->refresh();
    expect($otherAccount->trashed())->toBeFalse();
    
    // But it should still be removed from snapshot (since snapshot update doesn't check ownership)
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->not->toHaveKey($otherAccount->id);
});

it('handles empty accounts array after all deletions', function () {
    $this->actingAs($this->user);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Delete both accounts
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    $component->call('deleteAccount', $this->account2->id, $this->account2->name);
    
    // Verify empty accounts array
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->toBeEmpty();
    
    // Verify cash total is 0
    expect($component->get('cashTotal'))->toBe(0.0);
});

it('updates cash total after account deletion', function () {
    $this->actingAs($this->user);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Initial total should be 300 (100 + 200)
    expect($component->get('cashTotal'))->toBe(300.0);
    
    // Delete one account
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Total should now be 200 (just account2)
    expect($component->get('cashTotal'))->toBe(200.0);
});

it('prevents deletion when newer snapshots contain the account', function () {
    $this->actingAs($this->user);
    
    // Create a newer snapshot with the same account
    $newerSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [
            $this->account1->id => ['balance' => 500.00],
        ],
        'goals' => [],
        'debts' => [],
    ]);
    
    // Try to delete from the older snapshot
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Account should still be in the snapshot (not deleted)
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->toHaveKey($this->account1->id);
    
    // Account should not be soft deleted
    $this->account1->refresh();
    expect($this->account1->trashed())->toBeFalse();
    
    // Should show error notification (we can't directly test notifications in Livewire tests)
    // but we can verify the account wasn't deleted as evidence the validation worked
});

it('allows deletion when newer snapshots exist but do not contain the account', function () {
    $this->actingAs($this->user);
    
    // Create a newer snapshot with a different account
    $otherAccount = Account::factory()->create([
        'user_id' => $this->user->id,
        'retirement_type' => 'non_retirement'
    ]);
    
    $newerSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->addDay(),
        'accounts' => [
            $otherAccount->id => ['balance' => 500.00],
        ],
        'goals' => [],
        'debts' => [],
    ]);
    
    // Delete should succeed since newer snapshot doesn't contain our account
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Account should be removed from snapshot
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->not->toHaveKey($this->account1->id);
    
    // Account should be soft deleted
    $this->account1->refresh();
    expect($this->account1->trashed())->toBeTrue();
});

it('allows deletion when no newer snapshots exist', function () {
    $this->actingAs($this->user);
    
    // Create an older snapshot (this should not block deletion)
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [
            $this->account1->id => ['balance' => 50.00],
        ],
        'goals' => [],
        'debts' => [],
    ]);
    
    // Delete should succeed
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $component->call('deleteAccount', $this->account1->id, $this->account1->name);
    
    // Account should be removed from snapshot
    $this->snapshot->refresh();
    expect($this->snapshot->accounts)->not->toHaveKey($this->account1->id);
    
    // Account should be soft deleted
    $this->account1->refresh();
    expect($this->account1->trashed())->toBeTrue();
});