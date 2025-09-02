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
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    $otherAccount = Account::factory()->create(['user_id' => $this->user->id]);
    
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
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

it('handles null accounts gracefully', function () {
    $olderSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now()->subDay(),
        'accounts' => [$this->account->id => ['balance' => 100.00]],
        'goals' => [],
        'debts' => [],
    ]);

    // Create snapshot with null accounts using direct database insert since create() validates
    \DB::table('snapshots')->insert([
        'user_id' => $this->user->id,
        'date' => now()->toDateString(),
        'accounts' => null,
        'goals' => json_encode([]),
        'debts' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
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