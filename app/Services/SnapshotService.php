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
        return Snapshot::where('user_id', $userId)
            ->where('date', '>', $currentDate)
            ->whereNotNull('accounts')
            ->get()
            ->filter(function (Snapshot $snapshot) use ($accountId) {
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
}