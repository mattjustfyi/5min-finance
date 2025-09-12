<?php

use App\Filament\Resources\Snapshots\Pages\ListSnapshots;
use App\Models\Snapshot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Date Parsing Without Exception Handling', function () {
    it('parses dates correctly without masking other exceptions', function () {
        // Test that Carbon::parse works normally and the snapshot is created
        $date = now()->format('Y-m-d');
        
        Livewire::test(ListSnapshots::class)
            ->callAction('quickSnapshot', data: ['date' => $date])
            ->assertHasNoActionErrors();

        // Verify the snapshot was created successfully
        expect(Snapshot::count())->toBe(1);
        
        $snapshot = Snapshot::first();
        expect($snapshot->user_id)->toBe($this->user->id);
        expect($snapshot->date->format('Y-m-d'))->toBe($date);
    });

    it('correctly copies data when date parsing succeeds', function () {
        // Create previous snapshot
        Snapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDay(),
            'accounts' => [1 => ['balance' => 1000]],
            'goals' => [['goalId' => 1, 'targetAmount' => 5000]],
            'debts' => [['debtId' => 1, 'amount' => 2000]],
        ]);

        $newDate = now()->format('Y-m-d');

        Livewire::test(ListSnapshots::class)
            ->callAction('quickSnapshot', data: ['date' => $newDate])
            ->assertHasNoActionErrors();

        expect(Snapshot::count())->toBe(2);
        
        $newSnapshot = Snapshot::where('date', $newDate)->first();
        expect($newSnapshot->accounts)->toBe([1 => ['balance' => 1000]]);
        expect($newSnapshot->goals)->toBe([['goalId' => 1, 'targetAmount' => 5000]]);
        expect($newSnapshot->debts)->toBe([['debtId' => 1, 'amount' => 2000]]);
    });
});