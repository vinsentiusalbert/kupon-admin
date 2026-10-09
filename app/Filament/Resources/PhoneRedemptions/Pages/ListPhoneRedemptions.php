<?php

namespace App\Filament\Resources\PhoneRedemptions\Pages;

use App\Filament\Resources\Campaigns\CampaignsResource;
use App\Filament\Resources\PhoneRedemptions\PhoneRedemptionResource;
use App\Services\PhoneRedemptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ListPhoneRedemptions extends ListRecords
{
    protected static string $resource = PhoneRedemptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('redeemPhone')
                ->label('Input Nomor HP')
                ->icon(Heroicon::Plus)
                ->authorize(fn (): bool => PhoneRedemptionResource::canCreate())
                ->modalHeading('Redeem Nomor HP')
                ->modalSubmitActionLabel('Simpan & Redeem')
                ->schema([
                    Select::make('campaign_id')
                        ->label('Campaign')
                        ->options(fn () => CampaignsResource::getEloquentQuery()
                            ->whereNotNull('phone_outlet_code')->where('phone_outlet_code', '!=', '')
                            ->pluck('campaign_name', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('phone_number')
                        ->label('Nomor HP')
                        ->tel()
                        ->prefix('+62')
                        ->placeholder('81234567890')
                        ->helperText('Isi mulai angka 8, tanpa 0 atau +62. Kode outlet mengikuti campaign; waktu redeem dicatat otomatis.')
                        ->required()
                        ->regex('/^8[0-9]{8,11}$/')
                        ->validationMessages(['regex' => 'Isi nomor HP mulai angka 8, tanpa 0 atau +62.'])
                        ->minLength(9)
                        ->maxLength(12),
                ])
                ->action(function (array $data, Schema $schema): void {
                    try {
                        $campaign = CampaignsResource::getEloquentQuery()->find($data['campaign_id']);

                        if (! $campaign) {
                            throw ValidationException::withMessages([
                                'campaign_id' => 'Campaign tidak tersedia.',
                            ]);
                        }

                        $service = app(PhoneRedemptionService::class);
                        $redemption = $service->redeem($campaign, '+62'.$data['phone_number']);
                    } catch (ValidationException $exception) {
                        $errors = [];
                        foreach ($exception->errors() as $field => $messages) {
                            $errors[$schema->getStatePath().'.'.$field] = $messages;
                        }
                        throw ValidationException::withMessages($errors);
                    }

                    Notification::make()->success()
                        ->title('Nomor HP berhasil redeem')
                        ->body('Kode outlet: '.$redemption->outlet_code)
                        ->send();
                }),
        ];
    }
}
