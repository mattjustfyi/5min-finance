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
