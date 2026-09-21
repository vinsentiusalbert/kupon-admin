<?php

namespace App\Filament\Resources\Outlets;

use App\Filament\Resources\Outlets\Pages\CreateOutlets;
use App\Filament\Resources\Outlets\Pages\EditOutlets;
use App\Filament\Resources\Outlets\Pages\ListOutlets;
use App\Filament\Resources\Outlets\RelationManagers\VouchersRelationManager;
use App\Filament\Resources\Outlets\Schemas\OutletsForm;
use App\Filament\Resources\Outlets\Tables\OutletsTable;
use App\Models\Outlets;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OutletsResource extends Resource
{
    protected static ?string $model = Outlets::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?string $recordTitleAttribute = 'Outlets';

    public static function form(Schema $schema): Schema
    {
        return OutletsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutletsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        $roleNames = $user->getRoleNames();

        if ($roleNames->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'creator.roles',
            fn (Builder $roleQuery): Builder => $roleQuery->whereIn('name', $roleNames)
        );
    }

    public static function getRelations(): array
    {
        return [
            VouchersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutlets::route('/'),
            'create' => CreateOutlets::route('/create'),
            'edit' => EditOutlets::route('/{record}/edit'),
        ];
    }
}
