<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Enums\RetirementType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('currency')
                    ->badge()
                    ->sortable(),

                TextColumn::make('conversion_rate')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                TextColumn::make('retirement_type')
                    ->badge()
                    ->formatStateUsing(fn (RetirementType $state): string => match ($state) {
                        RetirementType::RETIREMENT => 'Retirement',
                        RetirementType::NON_RETIREMENT => 'Non-Retirement',
                    })
                    ->color(fn (RetirementType $state): string => match ($state) {
                        RetirementType::RETIREMENT => 'success',
                        RetirementType::NON_RETIREMENT => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('retirement_type')
                    ->options([
                        RetirementType::RETIREMENT->value => 'Retirement',
                        RetirementType::NON_RETIREMENT->value => 'Non-Retirement',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
