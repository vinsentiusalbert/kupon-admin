<?php

namespace App\Filament\Resources\Outlets\Schemas;

use App\Models\Campaigns;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutletsForm
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
                TextInput::make('outlet_name')
                    ->required(),
                TextInput::make('outlet_code')
                    ->required(),
                TextInput::make('voucher_code')
                    ->required(),
            ]);
    }
}
