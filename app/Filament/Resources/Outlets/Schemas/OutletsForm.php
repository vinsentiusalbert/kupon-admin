<?php

namespace App\Filament\Resources\Outlets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OutletsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('campaign_id')
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
