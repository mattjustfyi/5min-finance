<?php

namespace App\Filament\Resources\Snapshots\Pages;

use App\Filament\Resources\Snapshots\SnapshotResource;
use App\Models\Account;
use Filament\Resources\Pages\ViewRecord;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;

class ViewAccountLists extends ViewRecord
{
    
    protected static string $resource = SnapshotResource::class;

    public $accountBalances = [];

    public function mount($record): void
    {
        parent::mount($record);
        
        // Initialize editable balances from JSON structure
        foreach ($this->getCashAccounts() as $account) {
            $this->accountBalances[$account['id']] = $account['balance'];
        }
    }

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


    #[Computed]
    public function cashTotal(): float
    {
        // Use editable balances for live calculation
        $cashAccountIds = collect($this->getCashAccounts())->pluck('id')->toArray();
        return collect($this->accountBalances)
            ->only($cashAccountIds)
            ->sum();
    }

    public function getCashTotal(): float
    {
        return $this->cashTotal;
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();
        
        return [
            $resource::getUrl('index') => $resource::getBreadcrumb(),
            $resource::getUrl('edit', ['record' => $this->getRecord()]) => 'Edit',
            '' => 'Account List',
        ];
    }

    public function updateAccountBalances()
    {
        // Phase 1: Test Livewire action without page reload
        Notification::make()
            ->title('Save button clicked!')
            ->body('This is a Livewire action - no page reload.')
            ->success()
            ->send();
    }
}