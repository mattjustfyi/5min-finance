<?php

namespace App\Filament\Resources\GoalAllocations\Schemas;

use App\Enums\GoalType;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoalAllocationForm
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

                Select::make('type')
                    ->required()
                    ->options([
                        GoalType::EMERGENCY_FUND->value => 'Emergency Fund',
                        GoalType::SAVINGS_GOAL->value => 'Savings Goal',
                    ])
                    ->default(GoalType::SAVINGS_GOAL->value),
            ]);
    }
}
