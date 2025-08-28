<?php

namespace App\Filament\Resources\DebtAllocations;

use App\Filament\Resources\DebtAllocations\Pages\CreateDebtAllocation;
use App\Filament\Resources\DebtAllocations\Pages\EditDebtAllocation;
use App\Filament\Resources\DebtAllocations\Pages\ListDebtAllocations;
use App\Filament\Resources\DebtAllocations\Schemas\DebtAllocationForm;
use App\Filament\Resources\DebtAllocations\Tables\DebtAllocationsTable;
use App\Models\DebtAllocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DebtAllocationResource extends Resource
{
    protected static ?string $model = DebtAllocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function form(Schema $schema): Schema
    {
        return DebtAllocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DebtAllocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDebtAllocations::route('/'),
            'create' => CreateDebtAllocation::route('/create'),
            'edit' => EditDebtAllocation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Filament::auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
