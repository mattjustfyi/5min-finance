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

it('has add account action available', function () {
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Verify the addAccountAction method exists and returns an Action
    $action = $component->instance()->addAccountAction();
    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class);
    expect($action->getName())->toBe('addAccount');
});

it('has proper add account action configured', function () {
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $action = $component->instance()->addAccountAction();
    
    // Verify action configuration
    expect($action->getName())->toBe('addAccount');
    expect($action->getLabel())->toBe('Add');
    expect($action->getModalHeading())->toBe('Add New Account');
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

it('correctly counts newer snapshots', function () {
    // Create additional snapshots for the same user
    $newerSnapshot1 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->addDay(),
        'accounts' => [],
    ]);
    
    $newerSnapshot2 = Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->addDays(2),
        'accounts' => [],
    ]);
    
    // Create a snapshot for a different user (should not be counted)
    $otherUser = User::factory()->create();
    Snapshot::factory()->create([
        'user_id' => $otherUser->id,
        'date' => $this->snapshot->date->copy()->addDay(),
        'accounts' => [],
    ]);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    expect($component->get('newerSnapshotsCount'))->toBe(2);
});

it('correctly detects when older snapshots exist', function () {
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // Initially no older snapshots
    expect($component->get('hasOlderSnapshots'))->toBeFalse();
    
    // Create an older snapshot
    Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->subDay(),
        'accounts' => [],
    ]);
    
    // Test a new component instance to get fresh computed property
    $newComponent = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    expect($newComponent->get('hasOlderSnapshots'))->toBeTrue();
});

it('shows correct modal description when newer snapshots exist', function () {
    // Create newer snapshots
    Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->addDay(),
        'accounts' => [],
    ]);
    
    Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->addDays(2),
        'accounts' => [],
    ]);
    
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $action = $component->instance()->addAccountAction();
    
    // Get the modal description
    $description = $action->getModalDescription();
    
    expect($description)->toContain('Create a new account to track your finances.');
    expect($description)->toContain('Account will be added to 2 newer snapshot(s).');
});

it('includes date picker field in add account modal schema', function () {
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    $action = $component->instance()->addAccountAction();
    
    // Get the schema from the action's internal form
    $schemaReflection = (new \ReflectionClass($action))->getProperty('schema');
    $schemaReflection->setAccessible(true);
    $schema = $schemaReflection->getValue($action);
    
    $fieldNames = array_map(fn($field) => $field->getName(), $schema);
    expect($fieldNames)->toContain('addToEarlierSnapshotsDate');
});

it('shows correct modal state based on available snapshots', function () {
    $component = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    
    // With no newer snapshots, count should be 0
    expect($component->get('newerSnapshotsCount'))->toBe(0);
    expect($component->get('hasOlderSnapshots'))->toBeFalse();
    
    // Create an older snapshot
    Snapshot::factory()->create([
        'user_id' => $this->user->id,
        'date' => $this->snapshot->date->copy()->subDay(),
        'accounts' => [],
    ]);
    
    // Refresh component to get updated computed properties
    $newComponent = Livewire::test(ViewAccountLists::class, ['record' => $this->snapshot->id]);
    expect($newComponent->get('hasOlderSnapshots'))->toBeTrue();
});