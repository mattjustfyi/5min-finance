<?php

use App\Filament\Resources\Snapshots\Pages\ViewAccountLists;
use App\Models\Account;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Create a snapshot
    $this->snapshot = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'accounts' => [],
    ]);
});

it('can render the account list page', function () {
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->assertStatus(200)
        ->assertSee('Cash')
        ->assertSee('Add');
});

it('shows empty state when no accounts exist', function () {
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->assertSee('No cash accounts found');
});

it('can handle open modal event', function () {
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->dispatch('open-modal')
        ->assertDispatched('open-account-modal');
});

it('can handle account created event and update snapshot', function () {
    // Create an account
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account',
        'retirement_type' => 'non_retirement',
    ]);
    
    // Test the account-created event handler
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Verify initial state
    expect($this->snapshot->fresh()->accounts)->toBeEmpty();
    
    // Trigger the account-created event
    $component->dispatch('account-created', accountId: $account->id);
    
    // Check that the snapshot was updated
    $updatedSnapshot = $this->snapshot->fresh();
    expect($updatedSnapshot->accounts)->toHaveKey($account->id);
    expect($updatedSnapshot->accounts[$account->id])->toEqual(['balance' => 0.0]);
    
    // Check that the component state was updated
    $component->assertSet("accountBalances.{$account->id}", 0.0);
});

it('displays accounts from snapshot', function () {
    // Create an account
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account',
        'currency' => 'USD',
        'retirement_type' => 'non_retirement',
    ]);
    
    // Add account to snapshot
    $this->snapshot->update([
        'accounts' => [
            $account->id => ['balance' => 100.50]
        ]
    ]);
    
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->assertSee('Test Account')
        ->assertSee('USD')
        ->assertSet("accountBalances.{$account->id}", 100.50);
});

it('calculates total correctly', function () {
    // Create two accounts
    $account1 = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Account 1',
        'retirement_type' => 'non_retirement',
    ]);
    
    $account2 = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Account 2', 
        'retirement_type' => 'non_retirement',
    ]);
    
    // Add accounts to snapshot
    $this->snapshot->update([
        'accounts' => [
            $account1->id => ['balance' => 100.00],
            $account2->id => ['balance' => 200.50],
        ]
    ]);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Check the total calculation
    expect($component->get('cashTotal'))->toBe(300.5);
});

it('ignores retirement accounts', function () {
    // Create a retirement account
    $retirementAccount = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Retirement Account',
        'retirement_type' => 'retirement',
    ]);
    
    // Create a non-retirement account
    $cashAccount = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Cash Account',
        'retirement_type' => 'non_retirement',
    ]);
    
    // Add both accounts to snapshot
    $this->snapshot->update([
        'accounts' => [
            $retirementAccount->id => ['balance' => 1000.00],
            $cashAccount->id => ['balance' => 200.00],
        ]
    ]);
    
    Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->assertDontSee('Retirement Account')
        ->assertSee('Cash Account');
});

it('can update account balances', function () {
    // Create an account
    $account = Account::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Account',
        'retirement_type' => 'non_retirement',
    ]);
    
    // Add account to snapshot
    $this->snapshot->update([
        'accounts' => [
            $account->id => ['balance' => 100.00]
        ]
    ]);
    
    // Setting the property will trigger the updated lifecycle method
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id])
        ->set("accountBalances.{$account->id}", 250.75);
    
    // Check that the snapshot was updated in the database
    $updatedSnapshot = $this->snapshot->fresh();
    expect($updatedSnapshot->accounts[$account->id]['balance'])->toBe(250.75);
});