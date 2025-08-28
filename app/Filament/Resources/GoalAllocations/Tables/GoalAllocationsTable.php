<?php

namespace App\Filament\Resources\GoalAllocations\Tables;

use App\Enums\GoalType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class GoalAllocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (GoalType $state): string => match ($state) {
                        GoalType::EMERGENCY_FUND => 'Emergency Fund',
                        GoalType::SAVINGS_GOAL => 'Savings Goal',
                    })
                    ->color(fn (GoalType $state): string => match ($state) {
                        GoalType::EMERGENCY_FUND => 'danger',
                        GoalType::SAVINGS_GOAL => 'success',
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
                SelectFilter::make('type')
                    ->options([
                        GoalType::EMERGENCY_FUND->value => 'Emergency Fund',
                        GoalType::SAVINGS_GOAL->value => 'Savings Goal',
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
