<?php

namespace App\Filament\Resources\Snapshots\Pages;

use App\Filament\Resources\Snapshots\SnapshotResource;
use App\Models\Account;
use Filament\Resources\Pages\ViewRecord;
use Filament\Facades\Filament;

class ViewAccountLists extends ViewRecord
{
    protected static string $resource = SnapshotResource::class;

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
        ];
    }

    public function getView(): string
    {
        return 'filament.resources.snapshots.pages.view-account-lists';
    }

    public function getTitle(): string
    {
        return $this->record->date->format('j M Y');
    }

    public function getCashAccounts(): array
    {
        $accounts = [];
        
        if (!is_array($this->record->accounts)) {
            return $accounts;
        }

        foreach ($this->record->accounts as $accountId => $data) {
            $account = Account::withTrashed()
                ->where('user_id', Filament::auth()->id())
                ->find($accountId);

            if ($account && $account->retirement_type->value === 'non_retirement') {
                $accounts[] = [
                    'id' => $accountId,
                    'name' => $account->name,
                    'balance' => $data['balance'] ?? 0,
                    'currency' => $account->currency,
                    'conversion_rate' => $account->conversion_rate,
                ];
            }
        }

        return $accounts;
    }

    public function getCashTotal(): float
    {
        return collect($this->getCashAccounts())->sum('balance');
    }
}