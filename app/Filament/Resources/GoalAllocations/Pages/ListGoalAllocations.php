<?php

namespace App\Filament\Resources\GoalAllocations\Pages;

use App\Filament\Resources\GoalAllocations\GoalAllocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoalAllocations extends ListRecords
{
    protected static string $resource = GoalAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
