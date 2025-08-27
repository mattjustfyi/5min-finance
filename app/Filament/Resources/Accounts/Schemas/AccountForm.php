<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Enums\RetirementType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn (): int => Filament::auth()->id()),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('currency')
                    ->required()
                    ->length(3)
                    ->placeholder('USD')
                    ->rules(['regex:/^[A-Z]{3}$/'])
                    ->helperText('Enter 3-letter currency code (e.g., USD, EUR, GBP)'),

                TextInput::make('conversion_rate')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->rules(['min:0'])
                    ->helperText('Rate to convert account balance to your display currency'),

                Select::make('retirement_type')
                    ->required()
                    ->options([
                        RetirementType::RETIREMENT->value => 'Retirement',
                        RetirementType::NON_RETIREMENT->value => 'Non-Retirement',
                    ])
                    ->default(RetirementType::NON_RETIREMENT->value),
            ]);
    }
}
