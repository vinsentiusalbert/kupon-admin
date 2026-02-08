<?php

namespace App\Filament\Resources\Outlets;

use App\Filament\Resources\Outlets\Pages\CreateOutlets;
use App\Filament\Resources\Outlets\Pages\EditOutlets;
use App\Filament\Resources\Outlets\Pages\ListOutlets;
use App\Filament\Resources\Outlets\Schemas\OutletsForm;
use App\Filament\Resources\Outlets\Tables\OutletsTable;
use App\Models\Outlets;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

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

        return $query->where('created_by', $user->id);
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
            'index' => ListOutlets::route('/'),
            'create' => CreateOutlets::route('/create'),
            'edit' => EditOutlets::route('/{record}/edit'),
        ];
    }
}
