<?php

namespace App\Filament\Resources\Outlets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign.campaign_code')
                    ->label('Campaign')
                    ->formatStateUsing(function ($state, $record) {
                        if (! $record->campaign) {
                            return $record->campaign_id;
                        }

                        return $record->campaign->campaign_code . ' - ' . $record->campaign->campaign_name;
                    })
                    ->searchable(),
                TextColumn::make('outlet_name')
                    ->searchable(),
                TextColumn::make('outlet_code')
                    ->searchable(),
                TextColumn::make('voucher_code')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
