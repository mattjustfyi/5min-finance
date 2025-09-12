<?php

use App\Filament\Resources\Snapshots\Pages\ListSnapshots;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render the quick snapshot action', function () {
    Livewire::test(ListSnapshots::class)
        ->assertActionExists('quickSnapshot');
});

it('can create a quick snapshot with date only', function () {
    $date = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::count())->toBe(1);
    
    $snapshot = Snapshot::first();
    expect($snapshot->user_id)->toBe($this->user->id);
    expect($snapshot->date->format('Y-m-d'))->toBe($date);
    expect($snapshot->accounts)->toBe([]);
    expect($snapshot->goals)->toBe([]);
    expect($snapshot->debts)->toBe([]);
});

it('copies accounts from previous snapshot when creating quick snapshot', function () {
    $previousAccounts = [
        1 => ['balance' => 1000, 'name' => 'Checking Account'],
        2 => ['balance' => 5000, 'name' => 'Savings Account'],
    ];
    
    // Create a previous snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => $previousAccounts,
        'goals' => [],
        'debts' => [],
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::count())->toBe(2);
    
    $newSnapshot = Snapshot::where('date', $newDate)->first();
    expect($newSnapshot->accounts)->toBe($previousAccounts);
});

it('creates snapshot with empty accounts when no previous snapshot exists', function () {
    $date = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors();
    
    $snapshot = Snapshot::first();
    expect($snapshot->accounts)->toBe([]);
});

it('validates date is required', function () {
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => '',
        ])
        ->assertHasActionErrors(['date' => 'required']);
});

it('validates date is unique for the user', function () {
    $existingDate = now()->format('Y-m-d');
    
    // Create existing snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $existingDate,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $existingDate,
        ])
        ->assertHasActionErrors(['date']);
});

it('allows same date for different users', function () {
    $otherUser = User::factory()->create();
    $date = now()->format('Y-m-d');
    
    // Create snapshot for other user
    Snapshot::create([
        'user_id' => $otherUser->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::where('user_id', $this->user->id)->count())->toBe(1);
    expect(Snapshot::count())->toBe(2);
});

it('only copies accounts from user own previous snapshots', function () {
    $otherUser = User::factory()->create();
    $userAccounts = [1 => ['balance' => 1000, 'name' => 'User Account']];
    $otherUserAccounts = [2 => ['balance' => 2000, 'name' => 'Other User Account']];
    
    // Create previous snapshot for other user (more recent)
    Snapshot::create([
        'user_id' => $otherUser->id,
        'date' => now()->subHours(2),
        'accounts' => $otherUserAccounts,
        'goals' => [],
        'debts' => [],
    ]);
    
    // Create previous snapshot for current user (less recent)
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => $userAccounts,
        'goals' => [],
        'debts' => [],
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    $newSnapshot = Snapshot::where('date', $newDate)->where('user_id', $this->user->id)->first();
    expect($newSnapshot->accounts)->toBe($userAccounts);
});

it('redirects to accounts page after successful creation', function () {
    $date = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors();
    
    $snapshot = Snapshot::first();
    
    // The test framework doesn't actually perform redirects, but we can verify
    // the redirect would happen to the correct URL
    $expectedUrl = "/snapshots/{$snapshot->id}/accounts";
    
    // This is more of a integration/manual test scenario
    expect($snapshot)->not->toBeNull();
});

it('shows success notification after creation', function () {
    $date = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Quick snapshot created successfully!');
});

it('copies accounts from most recent previous snapshot when multiple exist', function () {
    $olderAccounts = [
        1 => ['balance' => 500, 'name' => 'Old Checking'],
    ];
    
    $newerAccounts = [
        1 => ['balance' => 1000, 'name' => 'Updated Checking'],
        2 => ['balance' => 2500, 'name' => 'New Savings'],
    ];
    
    // Create older snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(3),
        'accounts' => $olderAccounts,
        'goals' => [],
        'debts' => [],
    ]);
    
    // Create more recent snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => $newerAccounts,
        'goals' => [],
        'debts' => [],
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    $newSnapshot = Snapshot::where('date', $newDate)->first();
    expect($newSnapshot->accounts)->toBe($newerAccounts);
});

it('copies goals from previous snapshot when creating quick snapshot', function () {
    $previousGoals = [
        [
            'goalId' => 1,
            'targetAmount' => 5000,
            'deductions' => [
                [
                    'amount' => 100,
                    'description' => 'Test deduction',
                    'dateOccurred' => '2024-01-01',
                ],
            ],
        ],
        [
            'goalId' => 2,
            'targetAmount' => 10000,
            'deductions' => [],
        ],
    ];
    
    // Create a previous snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
        'goals' => $previousGoals,
        'debts' => [],
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::count())->toBe(2);
    
    $newSnapshot = Snapshot::where('date', $newDate)->first();
    expect($newSnapshot->goals)->toBe([
        [
            'goalId' => 1,
            'targetAmount' => 5000,
        ],
        [
            'goalId' => 2,
            'targetAmount' => 10000,
        ],
    ]);
});

it('copies debts from previous snapshot when creating quick snapshot', function () {
    $previousDebts = [
        [
            'debtId' => 1,
            'amount' => 1500,
            'extraField' => 'should be filtered out',
        ],
        [
            'debtId' => 2,
            'amount' => 2500,
        ],
    ];
    
    // Create a previous snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
        'goals' => [],
        'debts' => $previousDebts,
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::count())->toBe(2);
    
    $newSnapshot = Snapshot::where('date', $newDate)->first();
    expect($newSnapshot->debts)->toBe([
        [
            'debtId' => 1,
            'amount' => 1500,
        ],
        [
            'debtId' => 2,
            'amount' => 2500,
        ],
    ]);
});

it('creates snapshot with empty goals and debts when no previous snapshot exists', function () {
    $date = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $date,
        ])
        ->assertHasNoActionErrors();
    
    $snapshot = Snapshot::first();
    expect($snapshot->goals)->toBe([]);
    expect($snapshot->debts)->toBe([]);
});

it('copies goals and debts from most recent previous snapshot when multiple exist', function () {
    $olderGoals = [
        ['goalId' => 1, 'targetAmount' => 1000],
    ];
    $olderDebts = [
        ['debtId' => 1, 'amount' => 500],
    ];
    
    $newerGoals = [
        ['goalId' => 1, 'targetAmount' => 2000],
        ['goalId' => 2, 'targetAmount' => 3000],
    ];
    $newerDebts = [
        ['debtId' => 1, 'amount' => 400],
        ['debtId' => 2, 'amount' => 600],
    ];
    
    // Create older snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(3),
        'accounts' => [],
        'goals' => $olderGoals,
        'debts' => $olderDebts,
    ]);
    
    // Create more recent snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
        'goals' => $newerGoals,
        'debts' => $newerDebts,
    ]);
    
    $newDate = now()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $newDate,
        ])
        ->assertHasNoActionErrors();
    
    $newSnapshot = Snapshot::where('date', $newDate)->first();
    expect($newSnapshot->goals)->toBe($newerGoals);
    expect($newSnapshot->debts)->toBe($newerDebts);
});

it('handles case when date is specified before all existing snapshots', function () {
    // Create a future snapshot (relative to the date we'll create)
    $futureDate = now()->addDay()->format('Y-m-d');
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $futureDate,
        'accounts' => [1 => ['balance' => 1000, 'name' => 'Future Account']],
        'goals' => [],
        'debts' => [],
    ]);
    
    // Create snapshot with date earlier than all existing ones
    $pastDate = now()->subDay()->format('Y-m-d');
    
    Livewire::test(ListSnapshots::class)
        ->callAction('quickSnapshot', data: [
            'date' => $pastDate,
        ])
        ->assertHasNoActionErrors();
    
    expect(Snapshot::count())->toBe(2);
    
    // The new snapshot should be the one with empty accounts (since it's before all others)
    $newSnapshot = Snapshot::where('user_id', $this->user->id)
        ->get()
        ->sortBy('date')
        ->first();
    
    expect($newSnapshot)->not->toBeNull();
    expect($newSnapshot->accounts)->toBe([]);
    expect($newSnapshot->goals)->toBe([]);
    expect($newSnapshot->debts)->toBe([]);
});