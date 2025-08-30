<?php

namespace App\Filament\Resources\Snapshots\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SnapshotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('notes')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->searchable(),

                TextColumn::make('accounts')
                    ->label('Accounts')
                    ->html()
                    ->wrap()
                    ->extraAttributes(['style' => 'min-width: 300px; width: 300px;'])
                    ->getStateUsing(function ($record): string {
                        if (!is_array($record->accounts) || empty($record->accounts)) {
                            return 'No accounts';
                        }

                        $accountLines = [];
                        foreach ($record->accounts as $accountId => $data) {
                            $account = \App\Models\Account::withTrashed()
                                ->where('user_id', \Filament\Facades\Filament::auth()->id())
                                ->find($accountId);
                            
                            if ($account) {
                                $balance = $data['balance'] ?? 0;
                                $formattedBalance = $account->currency . ' ' . number_format($balance, 2);
                                $accountLines[] = "• {$account->name}: {$formattedBalance}";
                            }
                        }

                        return implode('<br>', $accountLines);
                    })
                    ->sortable(false),

                TextColumn::make('goals')
                    ->label('Goal Count')
                    ->formatStateUsing(function ($state): string {
                        return is_array($state) ? count($state) . ' goals' : '0 goals';
                    })
                    ->sortable(false),

                TextColumn::make('debts')
                    ->label('Debt Count')
                    ->formatStateUsing(function ($state): string {
                        return is_array($state) ? count($state) . ' debts' : '0 debts';
                    })
                    ->sortable(false),

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
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('accounts')
                    ->label('Accounts')
                    ->url(fn ($record) => route('filament.user.resources.snapshots.account-lists', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
