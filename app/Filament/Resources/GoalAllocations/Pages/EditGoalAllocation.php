<?php

namespace App\Filament\Resources\GoalAllocations\Pages;

use App\Filament\Resources\GoalAllocations\GoalAllocationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditGoalAllocation extends EditRecord
{
    protected static string $resource = GoalAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
