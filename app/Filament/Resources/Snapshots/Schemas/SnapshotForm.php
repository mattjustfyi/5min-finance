<?php

namespace App\Filament\Resources\Snapshots\Schemas;

use App\Models\Account;
use App\Models\GoalAllocation;
use App\Models\DebtAllocation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;

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
                    ->default(now()),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),

                KeyValue::make('accounts')
                    ->label('Account Balances')
                    ->keyLabel('Account ID')
                    ->valueLabel('Balance')
                    ->addable()
                    ->deletable()
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->helperText('Map account IDs to their balance amounts')
                    ->required(),

                Repeater::make('goals')
                    ->label('Goal Allocations')
                    ->schema([
                        TextInput::make('goalId')
                            ->label('Goal ID')
                            ->required(),
                        TextInput::make('targetAmount')
                            ->label('Target Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Repeater::make('deductions')
                            ->label('Deductions')
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required(),
                                TextInput::make('description')
                                    ->label('Description')
                                    ->required(),
                                DatePicker::make('dateOccurred')
                                    ->label('Date Occurred')
                                    ->required(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->collapsed()
                            ->cloneable()
                    ])
                    ->itemLabel(fn (array $state): ?string => 'Goal: ' . ($state['goalId'] ?? 'New'))
                    ->collapsed()
                    ->columnSpanFull()
                    ->required(),

                Repeater::make('debts')
                    ->label('Debt Allocations')
                    ->schema([
                        TextInput::make('debtId')
                            ->label('Debt ID')
                            ->required(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                    ])
                    ->itemLabel(fn (array $state): ?string => 'Debt: ' . ($state['debtId'] ?? 'New'))
                    ->collapsed()
                    ->columnSpanFull()
                    ->required(),
            ]);
    }
}
