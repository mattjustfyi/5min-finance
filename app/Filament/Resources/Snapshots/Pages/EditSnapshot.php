<?php

namespace App\Filament\Resources\Snapshots\Pages;

use App\Filament\Resources\Snapshots\SnapshotResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSnapshot extends EditRecord
{
    protected static string $resource = SnapshotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('accounts')
                ->label('Accounts')
                ->url(fn () => route('filament.user.resources.snapshots.account-lists', $this->getRecord())),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
