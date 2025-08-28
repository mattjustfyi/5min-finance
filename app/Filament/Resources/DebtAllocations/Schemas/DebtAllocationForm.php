<?php

namespace App\Filament\Resources\DebtAllocations\Schemas;

use App\Enums\DebtType;
use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DebtAllocationForm
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
                        DebtType::MONEY_OWED->value => 'Money Owed to Me',
                        DebtType::MONEY_OWING->value => 'Money I Owe',
                    ])
                    ->default(DebtType::MONEY_OWING->value),
            ]);
    }
}
