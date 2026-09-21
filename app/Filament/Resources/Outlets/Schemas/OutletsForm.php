<?php

namespace App\Filament\Resources\Outlets\Schemas;

use App\Filament\Resources\Campaigns\CampaignsResource;
use App\Models\Campaigns;
use App\Services\OutletVoucherService;
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
                        return CampaignsResource::getEloquentQuery()
                            ->orderBy('campaign_name')
                            ->get()
                            ->mapWithKeys(function (Campaigns $campaign) {
                                return [
                                    $campaign->id => $campaign->campaign_code.' - '.$campaign->campaign_name,
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
                TextInput::make('voucher_quantity')
                    ->label('Jumlah voucher')
                    ->helperText('Masukkan jumlah kode voucher yang akan dibuat otomatis. Setiap kode hanya dapat digunakan sekali.')
                    ->visibleOn('create')
                    ->required()
                    ->numeric()
                    ->rule('integer')
                    ->minValue(1)
                    ->maxValue(OutletVoucherService::MAX_VOUCHERS)
                    ->default(1),
            ]);
    }
}
