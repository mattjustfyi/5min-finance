<?php

namespace App\Filament\Resources\Snapshots\Pages;

use App\Filament\Resources\Snapshots\SnapshotResource;
use App\Models\Snapshot;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSnapshots extends ListRecords
{
    protected static string $resource = SnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickSnapshot')
                ->label('Quick Snapshot')
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary')
                ->form([
                    DatePicker::make('date')
                        ->label('Snapshot Date')
                        ->required()
                        ->native(false)
                        ->default(now())
                        ->unique(
                            table: Snapshot::class,
                            column: 'date',
                            ignoreRecord: false,
                            modifyRuleUsing: function ($rule) {
                                return $rule->where('user_id', Filament::auth()->id());
                            }
                        ),
                ])
                ->action(function (array $data): void {
                    $snapshotService = app(SnapshotService::class);
                    
                    $previousAccounts = $snapshotService->getPreviousSnapshotAccounts(
                        userId: Filament::auth()->id(),
                        beforeDate: Carbon::parse($data['date'])
                    );

                    $snapshot = Snapshot::create([
                        'user_id' => Filament::auth()->id(),
                        'date' => $data['date'],
                        'accounts' => $previousAccounts,
                        'goals' => [],
                        'debts' => [],
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Quick snapshot created successfully!')
                        ->body('You can now review and edit the account details.')
                        ->send();

                    $this->redirect(SnapshotResource::getUrl('account-lists', ['record' => $snapshot]));
                })
                ->modal(),
            CreateAction::make(),
        ];
    }
}
