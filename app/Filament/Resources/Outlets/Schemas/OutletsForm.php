<?php

namespace App\Filament\Resources\Outlets\Schemas;

use App\Models\Campaigns;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

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
                    ->required()
                    ->suffixAction(
                        Action::make('generate_voucher_code')
                            ->label('Generate')
                            ->icon(Heroicon::ArrowPath)
                            ->action(function (Set $set): void {
                                $set('voucher_code', self::generateVoucherCode());
                            })
                    ),
            ]);
    }

    private static function generateVoucherCode(): string
    {
        return Str::upper(Str::random(5));
    }
}
