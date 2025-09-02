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
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;

class ViewAccountLists extends ViewRecord implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    
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

            // Only include if account exists and is non-retirement
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


    public function addAccountAction(): Action
    {
        return Action::make('addAccount')
            ->label('Add')
            ->color('primary')
            ->size('xs')
            ->modalHeading('Add New Account')
            ->modalDescription('Create a new account to track your finances.')
            ->schema([
                TextInput::make('name')
                    ->label('Account Name')
                    ->placeholder('Enter account name')
                    ->required()
                    ->minLength(1)
                    ->maxLength(255)
                    ->unique('accounts', 'name', modifyRuleUsing: function ($rule) {
                        return $rule->where('user_id', Filament::auth()->id())->whereNull('deleted_at');
                    })
                    ->validationMessages([
                        'required' => 'Account name is required.',
                        'min' => 'Account name must be at least 1 character.',
                        'unique' => 'You already have an account with this name.',
                    ]),
                
                TextInput::make('currency')
                    ->label('Currency')
                    ->placeholder('USD')
                    ->required()
                    ->length(3)
                    ->extraAttributes(['style' => 'text-transform: uppercase'])
                    ->validationMessages([
                        'required' => 'Currency is required.',
                        'size' => 'Currency must be exactly 3 characters (e.g., USD, EUR).',
                    ])
                    ->helperText('3-letter currency code (e.g., USD, EUR, GBP)'),
                
                TextInput::make('conversionRate')
                    ->label('Conversion Rate')
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0.0001)
                    ->maxValue(999999.9999)
                    ->placeholder('1.0000')
                    ->required()
                    ->default(1.0)
                    ->validationMessages([
                        'required' => 'Conversion rate is required.',
                        'numeric' => 'Conversion rate must be a number.',
                        'min' => 'Conversion rate must be greater than 0.',
                    ])
                    ->helperText('Rate to convert this currency to your base currency (1.0 for base currency)'),
            ])
            ->action(function (array $data): void {
                try {
                    $accountService = app(AccountService::class);
                    
                    $account = $accountService->createAccount([
                        'name' => $data['name'],
                        'currency' => strtoupper($data['currency']),
                        'conversion_rate' => $data['conversionRate'],
                    ], Filament::auth()->id());

                    // Add the new account to the snapshot with balance 0
                    $currentAccounts = $this->record->accounts ?? [];
                    $currentAccounts[$account->id] = ['balance' => 0.0];
                    
                    $this->record->update(['accounts' => $currentAccounts]);
                    $this->accountBalances[$account->id] = 0.0;
                    
                    // Refresh computed properties
                    unset($this->cashTotal);

                    Notification::make()
                        ->title('Account created and added!')
                        ->body("Account '{$account->name}' has been created and added to this snapshot with a balance of 0.")
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error creating account')
                        ->body('An unexpected error occurred. Please try again.')
                        ->danger()
                        ->send();
                }
            });
    }

    public function deleteAccountAction(): Action
    {
        return Action::make('deleteAccount')
            ->label('Delete')
            ->color('danger')
            ->size('xs')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Delete Account')
            ->modalDescription(fn (array $arguments) => "Are you sure you want to delete the account? This action cannot be undone.")
            ->action(function (array $arguments): void {
                try {
                    $accountId = $arguments['accountId'];
                    
                    DB::transaction(function () use ($accountId) {
                        // Remove from snapshot accounts
                        $currentAccounts = $this->record->accounts ?? [];
                        unset($currentAccounts[$accountId]);
                        
                        // Update snapshot
                        $this->record->update(['accounts' => $currentAccounts]);
                        
                        // Soft delete the account (only if user owns it)
                        Account::where('id', $accountId)
                            ->where('user_id', Filament::auth()->id())
                            ->delete();
                    });

                    // Remove from local state
                    unset($this->accountBalances[$accountId]);
                    
                    // Refresh computed properties
                    unset($this->cashTotal);

                    Notification::make()
                        ->title('Account deleted')
                        ->body('The account has been deleted successfully.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error deleting account')
                        ->body('An unexpected error occurred. Please try again.')
                        ->danger()
                        ->send();
                }
            });
    }
}