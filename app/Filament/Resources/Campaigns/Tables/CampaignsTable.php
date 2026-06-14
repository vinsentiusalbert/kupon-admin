<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Models\Campaigns;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campaign_name')
                    ->searchable(),
                TextColumn::make('campaign_code')
                    ->searchable(),
                TextColumn::make('campaign_title')
                    ->searchable(),
                TextColumn::make('public_url')
                    ->label('Link')
                    ->state(fn (Campaigns $record): string => $record->public_url)
                    ->formatStateUsing(fn (): string => 'Buka Link')
                    ->url(fn (string $state): string => $state)
                    ->openUrlInNewTab()
                    ->copyable()
                    ->copyableState(fn (Campaigns $record): string => $record->public_url)
                    ->copyMessage('Link berhasil disalin'),
                ImageColumn::make('logo')
                    ->disk('public')
                    ->height(40),
                ImageColumn::make('image')
                    ->disk('public')
                    ->height(80),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
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
