<?php

namespace App\Filament\Resources\Snapshots\Pages;

use App\Filament\Resources\Snapshots\SnapshotResource;
use App\Models\Snapshot;
use App\Services\SnapshotService;
use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
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
                        ->disabledDates(function () {
                            return Snapshot::where('user_id', Filament::auth()->id())
                                ->pluck('date')
                                ->toArray();
                        })
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                $userId = Filament::auth()->id();

                                // Convert to date-only format to match how the model stores dates
                                $dateOnly = Carbon::parse($value)->toDateString();

                                $query = Snapshot::where('user_id', $userId)
                                    ->where('date', $dateOnly);

                                if ($query->exists()) {
                                    $fail('You already have a snapshot for this date. Please choose a different date.');
                                }
                            },
                        ]),
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
        ];
    }
}
