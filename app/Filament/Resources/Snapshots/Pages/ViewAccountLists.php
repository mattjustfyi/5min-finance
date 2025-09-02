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
use Livewire\Attributes\On;

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

    public function updatedAccountBalances($value, $key)
    {
        try {
            // Validate the specific field
            $this->validateOnly("accountBalances.{$key}", [
                'accountBalances.*' => ['required', 'numeric', 'min:0'],
            ]);

            // Get current accounts from database
            $currentAccounts = $this->record->accounts ?? [];

            // Update the specific balance
            if (isset($currentAccounts[$key])) {
                $currentAccounts[$key]['balance'] = (float) $value;
                
                // Save the updated accounts structure back to database
                $this->record->update([
                    'accounts' => $currentAccounts,
                ]);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Reset the invalid value
            $this->accountBalances[$key] = $currentAccounts[$key]['balance'] ?? 0;
            
            Notification::make()
                ->title('Invalid balance')
                ->body('Please enter a valid positive number.')
                ->danger()
                ->send();
        }
    }

    public function updateAccountBalances()
    {
        // Keep this method for any manual save operations if needed
        $this->validate([
            'accountBalances.*' => ['required', 'numeric', 'min:0'],
        ]);

        $currentAccounts = $this->record->accounts ?? [];

        foreach ($this->accountBalances as $accountId => $newBalance) {
            if (isset($currentAccounts[$accountId])) {
                $currentAccounts[$accountId]['balance'] = (float) $newBalance;
            }
        }

        $this->record->update([
            'accounts' => $currentAccounts,
        ]);

        Notification::make()
            ->title('Account balances updated!')
            ->body('All changes have been saved successfully.')
            ->success()
            ->send();
    }

    #[On('open-modal')]
    public function openAddAccountModal()
    {
        // Dispatch event to open the modal
        $this->dispatch('open-account-modal');
    }

    #[On('account-created')]
    public function handleAccountCreated($accountId)
    {
        // Add the new account to the snapshot with balance 0
        $currentAccounts = $this->record->accounts ?? [];
        
        // Add new account with 0 balance to snapshot
        $currentAccounts[$accountId] = [
            'balance' => 0.0,
        ];
        
        // Update the snapshot
        $this->record->update([
            'accounts' => $currentAccounts,
        ]);
        
        // Initialize the balance in our component state
        $this->accountBalances[$accountId] = 0.0;
        
        // Refresh the computed properties
        unset($this->cashTotal);
        
        // Show success notification
        $account = Account::find($accountId);
        Notification::make()
            ->title('Account added to snapshot!')
            ->body("'{$account->name}' has been added to this snapshot with a balance of 0.")
            ->success()
            ->send();
    }
}