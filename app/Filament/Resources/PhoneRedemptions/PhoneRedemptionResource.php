<?php

namespace App\Filament\Resources\PhoneRedemptions;

use App\Filament\Resources\Campaigns\CampaignsResource;
use App\Filament\Resources\PhoneRedemptions\Pages\ListPhoneRedemptions;
use App\Models\PhoneRedemption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhoneRedemptionResource extends Resource
{
    protected static ?string $model = PhoneRedemption::class;

    protected static ?string $navigationLabel = 'Redeem Nomor HP';

    protected static ?string $modelLabel = 'Redeem Nomor HP';

    protected static ?string $pluralModelLabel = 'Redeem Nomor HP';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Phone;

    public static function canViewAny(): bool
    {
        return auth()->user()?->roles()->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('campaign');

        if (! static::canViewAny()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('campaign_id', CampaignsResource::getEloquentQuery()->select('campaigns.id'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phone_number')->label('Nomor HP')->searchable(),
                TextColumn::make('campaign.campaign_name')->label('Campaign')->searchable(),
                TextColumn::make('outlet_code')->label('Kode outlet')->searchable(),
                TextColumn::make('redeemed_at')->label('Waktu redeem (WIB)')
                    ->dateTime('d/m/Y H:i:s', timezone: 'Asia/Jakarta')->sortable(),
            ])
            ->filters([
                SelectFilter::make('campaign_id')->label('Campaign')
                    ->options(fn () => CampaignsResource::getEloquentQuery()->pluck('campaign_name', 'id'))
                    ->searchable(),
            ])
            ->defaultSort('redeemed_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListPhoneRedemptions::route('/')];
    }
}
