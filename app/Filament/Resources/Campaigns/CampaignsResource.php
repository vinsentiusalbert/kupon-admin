<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaigns;
use App\Filament\Resources\Campaigns\Pages\EditCampaigns;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\Schemas\CampaignsForm;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaigns;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CampaignsResource extends Resource
{
    protected static ?string $model = Campaigns::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Megaphone;

    protected static ?string $recordTitleAttribute = 'Campaigns';

    public static function form(Schema $schema): Schema
    {
        return CampaignsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignsTable::configure($table);
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
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaigns::route('/create'),
            'edit' => EditCampaigns::route('/{record}/edit'),
        ];
    }
}
