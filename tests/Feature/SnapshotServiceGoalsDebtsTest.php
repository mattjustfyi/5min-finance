<?php

use App\Models\Snapshot;
use App\Models\User;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->snapshotService = app(SnapshotService::class);
});

describe('getPreviousSnapshotGoals', function () {
    it('returns empty array when no previous snapshot exists', function () {
        $result = $this->snapshotService->getPreviousSnapshotGoals($this->user->id);
        
        expect($result)->toBe([]);
    });

    it('returns empty array when previous snapshot has no goals', function () {
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [],
            'debts' => [],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotGoals($this->user->id);
        
        expect($result)->toBe([]);
    });

    it('returns goals with only goalId and targetAmount', function () {
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

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => $previousGoals,
            'debts' => [],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotGoals($this->user->id);
        
        expect($result)->toBe([
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

    it('returns goals from most recent snapshot before given date', function () {
        $olderGoals = [
            ['goalId' => 1, 'targetAmount' => 1000],
        ];
        $newerGoals = [
            ['goalId' => 1, 'targetAmount' => 2000],
        ];

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(3),
            'accounts' => [],
            'goals' => $olderGoals,
            'debts' => [],
        ]);

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => $newerGoals,
            'debts' => [],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotGoals($this->user->id, now());
        
        expect($result)->toBe([
            ['goalId' => 1, 'targetAmount' => 2000],
        ]);
    });

    it('only returns goals from same user', function () {
        $otherUser = User::factory()->create();
        
        Snapshot::create([
            'user_id' => $otherUser->id,
            'date' => now()->subHours(1),
            'accounts' => [],
            'goals' => [['goalId' => 999, 'targetAmount' => 9999]],
            'debts' => [],
        ]);

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [['goalId' => 1, 'targetAmount' => 1000]],
            'debts' => [],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotGoals($this->user->id, now());
        
        expect($result)->toBe([
            ['goalId' => 1, 'targetAmount' => 1000],
        ]);
    });
});

describe('getPreviousSnapshotDebts', function () {
    it('returns empty array when no previous snapshot exists', function () {
        $result = $this->snapshotService->getPreviousSnapshotDebts($this->user->id);
        
        expect($result)->toBe([]);
    });

    it('returns empty array when previous snapshot has no debts', function () {
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [],
            'debts' => [],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotDebts($this->user->id);
        
        expect($result)->toBe([]);
    });

    it('returns debts with only debtId and amount', function () {
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

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [],
            'debts' => $previousDebts,
        ]);

        $result = $this->snapshotService->getPreviousSnapshotDebts($this->user->id);
        
        expect($result)->toBe([
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

    it('returns debts from most recent snapshot before given date', function () {
        $olderDebts = [
            ['debtId' => 1, 'amount' => 1000],
        ];
        $newerDebts = [
            ['debtId' => 1, 'amount' => 800],
        ];

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(3),
            'accounts' => [],
            'goals' => [],
            'debts' => $olderDebts,
        ]);

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [],
            'debts' => $newerDebts,
        ]);

        $result = $this->snapshotService->getPreviousSnapshotDebts($this->user->id, now());
        
        expect($result)->toBe([
            ['debtId' => 1, 'amount' => 800],
        ]);
    });

    it('only returns debts from same user', function () {
        $otherUser = User::factory()->create();
        
        Snapshot::create([
            'user_id' => $otherUser->id,
            'date' => now()->subHours(1),
            'accounts' => [],
            'goals' => [],
            'debts' => [['debtId' => 999, 'amount' => 9999]],
        ]);

        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [],
            'goals' => [],
            'debts' => [['debtId' => 1, 'amount' => 1000]],
        ]);

        $result = $this->snapshotService->getPreviousSnapshotDebts($this->user->id, now());
        
        expect($result)->toBe([
            ['debtId' => 1, 'amount' => 1000],
        ]);
    });
});