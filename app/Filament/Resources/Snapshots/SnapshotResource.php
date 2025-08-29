<?php

namespace App\Filament\Resources\Snapshots;

use App\Filament\Resources\Snapshots\Pages\CreateSnapshot;
use App\Filament\Resources\Snapshots\Pages\EditSnapshot;
use App\Filament\Resources\Snapshots\Pages\ListSnapshots;
use App\Filament\Resources\Snapshots\Pages\ViewSnapshot;
use App\Filament\Resources\Snapshots\Schemas\SnapshotForm;
use App\Filament\Resources\Snapshots\Schemas\SnapshotInfolist;
use App\Filament\Resources\Snapshots\Tables\SnapshotsTable;
use App\Models\Snapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SnapshotResource extends Resource
{
    protected static ?string $model = Snapshot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    public static function form(Schema $schema): Schema
    {
        return SnapshotForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SnapshotInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SnapshotsTable::configure($table);
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
            'index' => ListSnapshots::route('/'),
            'create' => CreateSnapshot::route('/create'),
            'view' => ViewSnapshot::route('/{record}'),
            'edit' => EditSnapshot::route('/{record}/edit'),
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

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->where('user_id', Filament::auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
