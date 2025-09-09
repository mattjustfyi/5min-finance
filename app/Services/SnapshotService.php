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
}
