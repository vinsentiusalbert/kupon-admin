<?php

namespace App\Filament\Resources\Locations\Schemas;

use App\Models\Campaigns;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class LocationsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('campaign_id')
                    ->label('Campaign')
                    ->options(function () {
                        return Campaigns::query()
                            ->orderBy('campaign_name')
                            ->get()
                            ->mapWithKeys(function (Campaigns $campaign) {
                                return [
                                    $campaign->id => $campaign->campaign_code . ' - ' . $campaign->campaign_name,
                                ];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('addresss')
                    ->required(),
                TextInput::make('maps')
                    ->required()
                    ->url()
                    ->live(),
                Placeholder::make('maps_preview')
                    ->label('Preview')
                    ->content(function (Get $get): HtmlString {
                        $maps = trim((string) $get('maps'));

                        if ($maps === '') {
                            return new HtmlString('-');
                        }

                        $href = e($maps);

                        return new HtmlString(
                            '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">Buka di Google Maps</a>'
                        );
                    }),
            ]);
    }
}
