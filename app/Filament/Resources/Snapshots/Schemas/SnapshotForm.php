<?php

namespace App\Filament\Resources\Snapshots\Schemas;

use App\Models\Account;
use App\Models\DebtAllocation;
use App\Models\GoalAllocation;
use App\Models\Snapshot;
use Carbon\Carbon;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SnapshotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn (): int => Filament::auth()->id()),

                DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->disabledDates(function () {
                        $userId = Filament::auth()->id();
                        $recordId = request()->route('record');
                        
                        $query = Snapshot::where('user_id', $userId);
                        
                        // Exclude current record when editing
                        if ($recordId) {
                            $query->where('id', '!=', $recordId);
                        }
                        
                        return $query->pluck('date')->toArray();
                    })
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $userId = Filament::auth()->id();
                            $recordId = request()->route('record');

                            // Convert to date-only format to match how the model stores dates
                            $dateOnly = Carbon::parse($value)->toDateString();

                            $query = Snapshot::where('user_id', $userId)
                                ->where('date', $dateOnly);

                            // Exclude current record when editing
                            if ($recordId) {
                                $query->where('id', '!=', $recordId);
                            }

                            if ($query->exists()) {
                                $fail('You already have a snapshot for this date. Please choose a different date.');
                            }
                        },
                    ]),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),

                Repeater::make('accounts')
                    ->label('Account Balances')
                    ->schema([
                        Select::make('accountId')
                            ->label('Account')
                            ->options(function () {
                                return Account::withTrashed()
                                    ->where('user_id', Filament::auth()->id())
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('balance')
                            ->label('Balance')
                            ->rules(['required', 'numeric', 'min:0'])
                            ->inputMode('decimal')
                            ->placeholder('0.00'),
                    ])
                    ->itemLabel(function (array $state): ?string {
                        if (! isset($state['accountId'])) {
                            return 'New Account';
                        }
                        $account = Account::withTrashed()
                            ->where('user_id', Filament::auth()->id())
                            ->find($state['accountId']);

                        return $account?->name ?? 'ID '.$state['accountId'];
                    })
                    ->collapsed()
                    ->columnSpanFull()
                    ->mutateDehydratedStateUsing(function (array $state): array {
                        // Convert from form format to storage format
                        $accounts = [];
                        foreach ($state as $item) {
                            if (isset($item['accountId'], $item['balance'])) {
                                $accounts[$item['accountId']] = [
                                    'balance' => (float) $item['balance'],
                                ];
                            }
                        }

                        return $accounts;
                    })
                    ->afterStateHydrated(function (Repeater $component, $state) {
                        // Convert from storage format to form format
                        if (is_array($state)) {
                            $formData = [];
                            foreach ($state as $accountId => $data) {
                                $formData[] = [
                                    'accountId' => $accountId,
                                    'balance' => $data['balance'] ?? 0,
                                ];
                            }
                            $component->state($formData);
                        }
                    }),

                Repeater::make('goals')
                    ->label('Goal Allocations')
                    ->schema([
                        Select::make('goalId')
                            ->label('Goal')
                            ->options(function () {
                                return GoalAllocation::withTrashed()
                                    ->where('user_id', Filament::auth()->id())
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('targetAmount')
                            ->label('Target Amount')
                            ->rules(['required', 'numeric', 'min:0'])
                            ->inputMode('decimal')
                            ->placeholder('0.00'),
                        Repeater::make('deductions')
                            ->label('Deductions')
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->rules(['required', 'numeric', 'min:0'])
                                    ->inputMode('decimal')
                                    ->placeholder('0.00'),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required(),
                                DatePicker::make('dateOccurred')
                                    ->label('Date Occurred')
                                    ->required(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->collapsed()
                            ->cloneable(),
                    ])
                    ->itemLabel(function (array $state): ?string {
                        if (! isset($state['goalId'])) {
                            return 'New Goal';
                        }
                        $goal = GoalAllocation::withTrashed()
                            ->where('user_id', Filament::auth()->id())
                            ->find($state['goalId']);

                        return $goal?->name ?? 'ID '.$state['goalId'];
                    })
                    ->collapsed()
                    ->columnSpanFull(),

                Repeater::make('debts')
                    ->label('Debt Allocations')
                    ->schema([
                        Select::make('debtId')
                            ->label('Debt')
                            ->options(function () {
                                return DebtAllocation::withTrashed()
                                    ->where('user_id', Filament::auth()->id())
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->rules(['required', 'numeric', 'min:0'])
                            ->inputMode('decimal')
                            ->placeholder('0.00'),
                    ])
                    ->itemLabel(function (array $state): ?string {
                        if (! isset($state['debtId'])) {
                            return 'New Debt';
                        }
                        $debt = DebtAllocation::withTrashed()
                            ->where('user_id', Filament::auth()->id())
                            ->find($state['debtId']);

                        return $debt?->name ?? 'ID '.$state['debtId'];
                    })
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
