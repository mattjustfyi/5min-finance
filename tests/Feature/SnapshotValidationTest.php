<?php

use App\Models\Account;
use App\Models\DebtAllocation;
use App\Models\GoalAllocation;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->account1 = Account::factory()->create(['user_id' => $this->user->id]);
    $this->account2 = Account::factory()->create(['user_id' => $this->user->id]);
    $this->goal1 = GoalAllocation::factory()->create(['user_id' => $this->user->id]);
    $this->goal2 = GoalAllocation::factory()->create(['user_id' => $this->user->id]);
    $this->debt1 = DebtAllocation::factory()->create(['user_id' => $this->user->id]);
    $this->debt2 = DebtAllocation::factory()->create(['user_id' => $this->user->id]);
});

it('allows unique accounts', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [
            $this->account1->id => ['balance' => 100.00],
            $this->account2->id => ['balance' => 200.00],
        ],
        'goals' => [],
        'debts' => [],
    ]);

    expect($snapshot->save())->toBeTrue();
});

it('prevents duplicate goals', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'goals' => [
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal1->id, 'targetAmount' => 200], // Duplicate
        ]
    ]);

    expect(fn() => $snapshot->save())
        ->toThrow(\InvalidArgumentException::class, 'Duplicate goals are not allowed in a snapshot.');
});

it('prevents duplicate debts', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'debts' => [
            ['debtId' => $this->debt1->id, 'amount' => 100],
            ['debtId' => $this->debt1->id, 'amount' => 200], // Duplicate
        ]
    ]);

    expect(fn() => $snapshot->save())
        ->toThrow(\InvalidArgumentException::class, 'Duplicate debts are not allowed in a snapshot.');
});

it('allows unique goals and debts', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal2->id, 'targetAmount' => 200],
        ],
        'debts' => [
            ['debtId' => $this->debt1->id, 'amount' => 100],
            ['debtId' => $this->debt2->id, 'amount' => 200],
        ]
    ]);

    expect($snapshot->save())->toBeTrue();
});

it('prevents bypass via mass assignment', function () {
    expect(fn() => Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'goals' => [
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal1->id, 'targetAmount' => 200],
        ]
    ]))->toThrow(\InvalidArgumentException::class);
});

it('prevents bypass via update', function () {
    $snapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
        ],
        'debts' => [],
    ]);

    expect(fn() => $snapshot->update([
        'goals' => [
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal1->id, 'targetAmount' => 200],
        ]
    ]))->toThrow(\InvalidArgumentException::class);
});

it('prevents bypass via direct property assignment', function () {
    $snapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    $snapshot->goals = [
        ['goalId' => $this->goal1->id, 'targetAmount' => 100],
        ['goalId' => $this->goal1->id, 'targetAmount' => 200],
    ];

    expect(fn() => $snapshot->save())->toThrow(\InvalidArgumentException::class);
});

it('CAN be bypassed via query builder - VULNERABILITY FOUND!', function () {
    $snapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    // This bypasses model validation because it directly updates the database
    Snapshot::where('id', $snapshot->id)->update([
        'goals' => json_encode([
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal1->id, 'targetAmount' => 200],
        ])
    ]);

    // Let's verify the bypass worked
    $snapshot->refresh();
    $goalIds = collect($snapshot->goals)->pluck('goalId')->filter()->toArray();

    expect($goalIds)->toHaveCount(2);
    expect($goalIds[0])->toBe($goalIds[1]); // Both are the same ID
});

it('CAN be bypassed via insert - VULNERABILITY FOUND!', function () {
    // Direct DB insert bypasses model events entirely
    \DB::table('snapshots')->insert([
        'user_id' => $this->user->id,
        'date' => now()->toDateString(),
        'accounts' => json_encode([]),
        'goals' => json_encode([
            ['goalId' => $this->goal1->id, 'targetAmount' => 100],
            ['goalId' => $this->goal1->id, 'targetAmount' => 200],
        ]),
        'debts' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $snapshot = Snapshot::first();
    $goalIds = collect($snapshot->goals)->pluck('goalId')->filter()->toArray();

    expect($goalIds)->toHaveCount(2);
    expect($goalIds[0])->toBe($goalIds[1]); // Both are the same ID
});

it('handles empty arrays', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    expect($snapshot->save())->toBeTrue();
});

it('handles null values', function () {
    $snapshot = new Snapshot([
        'user_id' => $this->user->id,
        'date' => now(),
        'accounts' => [],
        'goals' => [],
        'debts' => [],
        'notes' => 'Test snapshot with null values',
    ]);

    expect($snapshot->save())->toBeTrue();
});

it('prevents duplicate snapshots for same user and date via database constraint', function () {
    $date = now()->toDateString();

    // Create first snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    // Attempt to create second snapshot with same user_id and date
    expect(fn() => Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('allows same date for different users via database constraint', function () {
    $anotherUser = User::factory()->create();
    $date = now()->toDateString();

    // Create snapshot for first user
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    // Create snapshot for second user with same date - should succeed
    $secondSnapshot = Snapshot::create([
        'user_id' => $anotherUser->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    expect($secondSnapshot->exists)->toBeTrue();
    expect(Snapshot::count())->toBe(2);
});

it('allows different dates for same user via database constraint', function () {
    $today = now()->toDateString();
    $tomorrow = now()->addDay()->toDateString();

    // Create first snapshot
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $today,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    // Create second snapshot with different date - should succeed
    $secondSnapshot = Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $tomorrow,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    expect($secondSnapshot->exists)->toBeTrue();
    expect(Snapshot::count())->toBe(2);
});

it('enforces constraint even with direct database operations', function () {
    $date = now()->toDateString();

    // Create first snapshot via Eloquent
    Snapshot::create([
        'user_id' => $this->user->id,
        'date' => $date,
        'accounts' => [],
        'goals' => [],
        'debts' => [],
    ]);

    // Attempt direct database insert with duplicate user_id/date
    expect(fn() => \DB::table('snapshots')->insert([
        'user_id' => $this->user->id,
        'date' => $date,
        'accounts' => json_encode([]),
        'goals' => json_encode([]),
        'debts' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
