<?php

namespace App\Filament\Resources\DebtAllocations\Pages;

use App\Filament\Resources\DebtAllocations\DebtAllocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebtAllocations extends ListRecords
{
    protected static string $resource = DebtAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
