<?php

use App\Models\Account;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\SnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account = Account::factory()->create(['user_id' => $this->user->id]);
    $this->snapshotService = new SnapshotService();
});

it('returns false when no newer snapshots exist', function () {
    $snapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $hasNewer = $this->snapshotService->hasNewerSnapshotsWithAccount(
        $this->account->id,
        $snapshot->date,
        $this->user->id
    );

    expect($hasNewer)->toBeFalse();
});

it('returns false when newer snapshots exist but do not contain the account', function () {
    $olderDate = now()->subDay();
    $newerDate = now();

    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $olderDate,
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);

    $newerSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $newerDate,
        'accounts' => [$otherAccount->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $hasNewer = $this->snapshotService->hasNewerSnapshotsWithAccount(
        $this->account->id,
        $olderSnapshot->date,
        $this->user->id
    );

    expect($hasNewer)->toBeFalse();
});

it('returns true when newer snapshots exist and contain the account', function () {
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [$this->account->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $hasNewer = $this->snapshotService->hasNewerSnapshotsWithAccount(
        $this->account->id,
        $olderSnapshot->date,
        $this->user->id
    );

    expect($hasNewer)->toBeTrue();
});

it('only considers snapshots for the specified user', function () {
    $otherUser = User::factory()->create();

    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    // Create newer snapshot for different user
    Snapshot::create([
        'user_id' => $otherUser->id,
        'date' => now(),
        'accounts' => [$this->account->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $hasNewer = $this->snapshotService->hasNewerSnapshotsWithAccount(
        $this->account->id,
        $olderSnapshot->date,
        $this->user->id
    );

    expect($hasNewer)->toBeFalse();
});


it('handles empty accounts array gracefully', function () {
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    $hasNewer = $this->snapshotService->hasNewerSnapshotsWithAccount(
        $this->account->id,
        $olderSnapshot->date,
        $this->user->id
    );

    expect($hasNewer)->toBeFalse();
});

it('returns newer snapshots with account in chronological order', function () {
    $baseDate = now()->subDays(3);

    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate,
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $newerSnapshot1 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDay(),
        'accounts' => [$this->account->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $newerSnapshot2 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDays(2),
        'accounts' => [$this->account->id => ['balance' => 300.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $newerSnapshots = $this->snapshotService->getNewerSnapshotsWithAccount(
        $this->account->id,
        $olderSnapshot->date,
        $this->user->id
    );

    expect($newerSnapshots)->toHaveCount(2);
    expect($newerSnapshots->first()->id)->toBe($newerSnapshot1->id);
    expect($newerSnapshots->last()->id)->toBe($newerSnapshot2->id);
});

it('returns empty array when no previous snapshots exist', function () {
    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id);

    expect($accounts)->toBe([]);
});

it('returns empty array when no previous snapshots exist before specified date', function () {
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id, now()->subDay());

    expect($accounts)->toBe([]);
});

it('returns accounts from the most recent previous snapshot', function () {
    $expectedAccounts = [
        $this->account->id => ['balance' => 200, 'name' => 'Test Account'],
        99 => ['balance' => 300, 'name' => 'Another Account']
    ];

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(2),
        'accounts' => [$this->account->id => ['balance' => 100]],
        'goals' => [],
        'debts' => [],
    ]);

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => $expectedAccounts,
        'goals' => [],
        'debts' => [],
    ]);

    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id);

    expect($accounts)->toBe($expectedAccounts);
});

it('returns accounts from previous snapshot before specified date', function () {
    $olderAccounts = [
        $this->account->id => ['balance' => 100, 'name' => 'Old Account']
    ];

    $newerAccounts = [
        $this->account->id => ['balance' => 200, 'name' => 'New Account']
    ];

//    Snapshot::create(['user_id' => 11, 'date' => now()->subDays(2), 'accounts' => ['41' => ['balance' => 100, 'name' => 'Old Account']], 'goals' => [], 'debts' => []]);

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(2),
        'accounts' => $olderAccounts,
        'goals' => [],
        'debts' => [],
    ]);

    $newerSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => $newerAccounts,
        'goals' => [],
        'debts' => [],
    ]);

    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id, $newerSnapshot->date);

    expect($accounts)->toBe($olderAccounts);
});


it('returns empty array when previous snapshot has empty accounts', function () {
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id);

    expect($accounts)->toBe([]);
});

it('only considers snapshots for the specified user when getting previous accounts', function () {
    $otherUser = User::factory()->create();
    $otherUserAccounts = [999 => ['balance' => 999, 'name' => 'Other User Account']];
    $thisUserAccounts = [$this->account->id => ['balance' => 100, 'name' => 'This User Account']];

    Snapshot::create([
        'user_id' => $otherUser->id,
        'date' => now()->subDay(),
        'accounts' => $otherUserAccounts,
        'goals' => [],
        'debts' => [],
    ]);

    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDays(2),
        'accounts' => $thisUserAccounts,
        'goals' => [],
        'debts' => [],
    ]);

    $accounts = $this->snapshotService->getPreviousSnapshotAccounts($this->user->id);

    expect($accounts)->toBe($thisUserAccounts);
});

it('returns snapshots within date range in chronological order', function () {
    $baseDate = now()->subDays(5);
    
    $snapshot1 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $snapshot2 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDays(3),
        'accounts' => [$this->account->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    // Create snapshot outside range
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDays(6),
        'accounts' => [$this->account->id => ['balance' => 300.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $snapshots = $this->snapshotService->getSnapshotsInDateRange(
        $this->user->id,
        $baseDate,
        $baseDate->copy()->addDays(4)
    );
    
    expect($snapshots)->toHaveCount(2);
    expect($snapshots->first()->id)->toBe($snapshot1->id);
    expect($snapshots->last()->id)->toBe($snapshot2->id);
});

it('returns empty collection when no snapshots exist in date range', function () {
    $baseDate = now()->subDays(5);
    
    $snapshots = $this->snapshotService->getSnapshotsInDateRange(
        $this->user->id,
        $baseDate,
        $baseDate->copy()->addDay()
    );
    
    expect($snapshots)->toBeEmpty();
});

it('returns all newer snapshots in chronological order', function () {
    $baseDate = now()->subDays(3);
    
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate,
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $newerSnapshot1 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDay(),
        'accounts' => [$this->account->id => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $newerSnapshot2 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $baseDate->copy()->addDays(2),
        'accounts' => [$this->account->id => ['balance' => 300.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $newerSnapshots = $this->snapshotService->getAllNewerSnapshots(
        $this->user->id,
        $olderSnapshot->date
    );
    
    expect($newerSnapshots)->toHaveCount(2);
    expect($newerSnapshots->first()->id)->toBe($newerSnapshot1->id);
    expect($newerSnapshots->last()->id)->toBe($newerSnapshot2->id);
});

it('returns true when account does not exist in any snapshots', function () {
    $snapshots = collect([
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now(),
            'accounts' => [999 => ['balance' => 100.00]],
            'goals' => [],
            'debts' => [],
        ])
    ]);
    
    $isValid = $this->snapshotService->validateAccountNotInSnapshots(
        $this->account->id,
        $snapshots
    );
    
    expect($isValid)->toBeTrue();
});

it('returns false when account exists in at least one snapshot', function () {
    $snapshots = collect([
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [999 => ['balance' => 100.00]],
            'goals' => [],
            'debts' => [],
        ]),
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now(),
            'accounts' => [$this->account->id => ['balance' => 200.00]],
            'goals' => [],
            'debts' => [],
        ])
    ]);
    
    $isValid = $this->snapshotService->validateAccountNotInSnapshots(
        $this->account->id,
        $snapshots
    );
    
    expect($isValid)->toBeFalse();
});

it('adds account to multiple snapshots with balance 0', function () {
    $snapshot1 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [999 => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $snapshot2 = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [888 => ['balance' => 200.00]],
        'goals' => [],
        'debts' => [],
    ]);
    
    $snapshots = collect([$snapshot1, $snapshot2]);
    
    $this->snapshotService->addAccountToMultipleSnapshots(
        $this->account->id,
        $snapshots
    );
    
    $snapshot1->refresh();
    $snapshot2->refresh();
    
    expect($snapshot1->accounts)->toHaveKey($this->account->id);
    expect($snapshot1->accounts[$this->account->id]['balance'])->toBe(0);
    expect($snapshot2->accounts)->toHaveKey($this->account->id);
    expect($snapshot2->accounts[$this->account->id]['balance'])->toBe(0);
    
    // Ensure other accounts are preserved
    expect($snapshot1->accounts)->toHaveKey(999);
    expect($snapshot2->accounts)->toHaveKey(888);
});
