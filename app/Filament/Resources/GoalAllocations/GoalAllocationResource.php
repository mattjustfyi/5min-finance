<?php

namespace App\Filament\Resources\GoalAllocations;

use App\Filament\Resources\GoalAllocations\Pages\CreateGoalAllocation;
use App\Filament\Resources\GoalAllocations\Pages\EditGoalAllocation;
use App\Filament\Resources\GoalAllocations\Pages\ListGoalAllocations;
use App\Filament\Resources\GoalAllocations\Schemas\GoalAllocationForm;
use App\Filament\Resources\GoalAllocations\Tables\GoalAllocationsTable;
use App\Models\GoalAllocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GoalAllocationResource extends Resource
{
    protected static ?string $model = GoalAllocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function form(Schema $schema): Schema
    {
        return GoalAllocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoalAllocationsTable::configure($table);
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
            'index' => ListGoalAllocations::route('/'),
            'create' => CreateGoalAllocation::route('/create'),
            'edit' => EditGoalAllocation::route('/{record}/edit'),
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
