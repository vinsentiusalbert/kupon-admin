<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\CreateLocations;
use App\Filament\Resources\Locations\Pages\EditLocations;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Locations\Schemas\LocationsForm;
use App\Filament\Resources\Locations\Tables\LocationsTable;
use App\Models\Locations;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationsResource extends Resource
{
    protected static ?string $navigationLabel = 'Location';

    protected static ?string $model = Locations::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LocationsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
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
            'campaign.creator.roles',
            fn (Builder $roleQuery): Builder => $roleQuery->whereIn('name', $roleNames)
        );
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
            'index' => ListLocations::route('/'),
            'create' => CreateLocations::route('/create'),
            'edit' => EditLocations::route('/{record}/edit'),
        ];
    }
}
