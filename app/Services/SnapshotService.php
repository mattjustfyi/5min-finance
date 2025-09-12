<?php

namespace App\Services;

use App\Models\Snapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SnapshotService
{
    /**
     * Check if there are any snapshots newer than the given date that contain the specified account.
     *
     * @param int $accountId
     * @param Carbon $currentDate
     * @param int $userId
     * @return bool
     */
    public function hasNewerSnapshotsWithAccount(int $accountId, Carbon $currentDate, int $userId): bool
    {
        $snapshots = Snapshot::where('user_id', $userId)
            ->where('date', '>', $currentDate)
            ->whereNotNull('accounts')
            ->get();

        return $snapshots->filter(function (Snapshot $snapshot) use ($accountId) {
            $accounts = $snapshot->accounts ?? [];
            return is_array($accounts) && array_key_exists($accountId, $accounts);
        })
            ->isNotEmpty();
    }

    /**
     * Get snapshots newer than the given date that contain the specified account.
     *
     * @param int $accountId
     * @param Carbon $currentDate
     * @param int $userId
     * @return Collection
     */
    public function getNewerSnapshotsWithAccount(int $accountId, Carbon $currentDate, int $userId): Collection
    {
        return Snapshot::where('user_id', $userId)
            ->where('date', '>', $currentDate)
            ->whereNotNull('accounts')
            ->orderBy('date')
            ->get()
            ->filter(function (Snapshot $snapshot) use ($accountId) {
                $accounts = $snapshot->accounts ?? [];
                return is_array($accounts) && array_key_exists($accountId, $accounts);
            });
    }

    /**
     * Get accounts from the previous snapshot for the specified user.
     *
     * @param int $userId
     * @param Carbon|null $beforeDate
     * @return array
     */
    public function getPreviousSnapshotAccounts(int $userId, ?Carbon $beforeDate = null): array
    {
        $query = Snapshot::where('user_id', $userId);

        if ($beforeDate) {
//            The date needs to have time stripped because SQLite essentially does a string comparison for dates. So
            // 2022-01-01 < 2022-01-01 00:00:00 is actually TRUE, when it should be FALSE.
            $query->where('date', '<', $beforeDate->format('Y-m-d'));
        }

        $previousSnapshot = $query->orderBy('date', 'desc')->first();

        if (!$previousSnapshot) {
            return [];
        }

        if (!is_array($previousSnapshot->accounts) || empty($previousSnapshot->accounts)) {
            return [];
        }

        return $previousSnapshot->accounts;
    }

    /**
     * Get goals from the previous snapshot for the specified user (target amounts only).
     *
     * @param int $userId
     * @param Carbon|null $beforeDate
     * @return array
     */
    public function getPreviousSnapshotGoals(int $userId, ?Carbon $beforeDate = null): array
    {
        $query = Snapshot::where('user_id', $userId);

        if ($beforeDate) {
            $query->where('date', '<', $beforeDate->format('Y-m-d'));
        }

        $previousSnapshot = $query->orderBy('date', 'desc')->first();

        if (!$previousSnapshot) {
            return [];
        }

        if (!is_array($previousSnapshot->goals) || empty($previousSnapshot->goals)) {
            return [];
        }

        return collect($previousSnapshot->goals)->map(function ($goal) {
            return [
                'goalId' => $goal['goalId'],
                'targetAmount' => $goal['targetAmount'],
            ];
        })->toArray();
    }

    /**
     * Get debts from the previous snapshot for the specified user.
     *
     * @param int $userId
     * @param Carbon|null $beforeDate
     * @return array
     */
    public function getPreviousSnapshotDebts(int $userId, ?Carbon $beforeDate = null): array
    {
        $query = Snapshot::where('user_id', $userId);

        if ($beforeDate) {
            $query->where('date', '<', $beforeDate->format('Y-m-d'));
        }

        $previousSnapshot = $query->orderBy('date', 'desc')->first();

        if (!$previousSnapshot) {
            return [];
        }

        if (!is_array($previousSnapshot->debts) || empty($previousSnapshot->debts)) {
            return [];
        }

        return collect($previousSnapshot->debts)->map(function ($debt) {
            return [
                'debtId' => $debt['debtId'],
                'amount' => $debt['amount'],
            ];
        })->toArray();
    }

    /**
     * Get snapshots within a date range for the specified user.
     *
     * @param int $userId
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @return Collection
     */
    public function getSnapshotsInDateRange(int $userId, Carbon $fromDate, Carbon $toDate): Collection
    {
        return Snapshot::where('user_id', $userId)
            ->where('date', '>=', $fromDate->format('Y-m-d'))
            ->where('date', '<=', $toDate->format('Y-m-d'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Get all snapshots newer than the given date for the specified user.
     *
     * @param int $userId
     * @param Carbon $currentDate
     * @return Collection
     */
    public function getAllNewerSnapshots(int $userId, Carbon $currentDate): Collection
    {
        return Snapshot::where('user_id', $userId)
            ->where('date', '>', $currentDate->format('Y-m-d'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Check if an account already exists in any of the given snapshots.
     *
     * @param int $accountId
     * @param Collection $snapshots
     * @return bool
     */
    public function validateAccountNotInSnapshots(int $accountId, Collection $snapshots): bool
    {
        foreach ($snapshots as $snapshot) {
            $accounts = $snapshot->accounts ?? [];
            if (is_array($accounts) && array_key_exists($accountId, $accounts)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add an account to multiple snapshots with balance 0 in a transaction.
     *
     * @param int $accountId
     * @param Collection $snapshots
     * @return void
     * @throws \Exception
     */
    public function addAccountToMultipleSnapshots(int $accountId, Collection $snapshots): void
    {
        // Performance check - warn if trying to update too many snapshots
        if ($snapshots->count() > 100) {
            throw new \InvalidArgumentException('Cannot update more than 100 snapshots at once for performance reasons.');
        }
        
        \DB::transaction(function () use ($accountId, $snapshots) {
            foreach ($snapshots as $snapshot) {
                $currentAccounts = $snapshot->accounts ?? [];
                
                // Additional validation - ensure we don't already have this account
                if (array_key_exists($accountId, $currentAccounts)) {
                    throw new \InvalidArgumentException("Account {$accountId} already exists in snapshot {$snapshot->id}");
                }
                
                $currentAccounts[$accountId] = ['balance' => 0];
                
                $snapshot->update(['accounts' => $currentAccounts]);
            }
        });
    }
}
