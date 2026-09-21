<?php

namespace App\Filament\Resources\Outlets\RelationManagers;

use App\Filament\Resources\Outlets\OutletsResource;
use App\Models\OutletVoucher;
use App\Services\OutletVoucherService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VouchersRelationManager extends RelationManager
{
    protected static string $relationship = 'vouchers';

    protected static ?string $title = 'Kode voucher';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->headerActions([
                Action::make('addVouchers')
                    ->label('Tambah voucher')
                    ->icon(Heroicon::Plus)
                    ->authorize(fn (): bool => $this->canManageVouchers())
                    ->modalHeading('Tambah voucher')
                    ->modalSubmitActionLabel('Generate voucher')
                    ->schema([
                        TextInput::make('voucher_quantity')
                            ->label('Jumlah voucher tambahan')
                            ->helperText('Contoh: isi 10 untuk menambahkan 10 kode voucher baru.')
                            ->required()
                            ->numeric()
                            ->rule('integer')
                            ->minValue(1)
                            ->maxValue(OutletVoucherService::MAX_VOUCHERS)
                            ->default(10),
                    ])
                    ->action(function (array $data, Schema $schema): void {
                        try {
                            app(OutletVoucherService::class)->addVouchers($this->getOwnerRecord(), $data);
                        } catch (ValidationException $exception) {
                            // Map capacity errors from the locked transaction to the action form.
                            throw ValidationException::withMessages([
                                $schema->getStatePath().'.voucher_quantity' => $exception->errors()['voucher_quantity'],
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title($data['voucher_quantity'].' voucher berhasil ditambahkan')
                            ->send();
                    }),
                Action::make('downloadCsv')
                    ->label('Download CSV')
                    ->icon(Heroicon::ArrowDownTray)
                    ->action(function (): StreamedResponse {
                        $outlet = $this->getOwnerRecord();

                        return response()->streamDownload(function () use ($outlet): void {
                            $stream = fopen('php://output', 'w');
                            // Include a UTF-8 BOM for spreadsheet applications such as Excel.
                            fwrite($stream, "\xEF\xBB\xBF");
                            fputcsv($stream, ['Kode voucher', 'Status', 'Waktu redeem'], escape: '');

                            foreach ($outlet->vouchers()->lazyById() as $voucher) {
                                fputcsv($stream, [
                                    $voucher->code,
                                    $voucher->redeemed_at ? 'Sudah digunakan' : 'Tersedia',
                                    $voucher->redeemed_at?->format('Y-m-d H:i:s') ?? '',
                                ], escape: '');
                            }

                            fclose($stream);
                        }, 'voucher-outlet-'.$outlet->id.'.csv', [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->columns([
                TextColumn::make('code')
                    ->label('Kode voucher')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (OutletVoucher $record): string => $record->redeemed_at ? 'Sudah digunakan' : 'Tersedia')
                    ->badge()
                    ->color(fn (OutletVoucher $record): string => $record->redeemed_at ? 'gray' : 'success'),
                TextColumn::make('redeemed_at')
                    ->label('Waktu redeem')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->recordActions([
                Action::make('resetRedemption')
                    ->label('Reset redeem')
                    ->icon(Heroicon::ArrowPath)
                    ->color('warning')
                    ->authorize(fn (): bool => $this->canManageVouchers())
                    ->visible(fn (OutletVoucher $record): bool => $record->redeemed_at !== null)
                    ->action(function (OutletVoucher $record): void {
                        app(OutletVoucherService::class)->resetRedemption($this->getOwnerRecord(), $record);

                        Notification::make()
                            ->success()
                            ->title('Voucher kembali tersedia dan waktu redeem dikosongkan')
                            ->send();
                    }),
            ])
            ->defaultSort('id');
    }

    private function canManageVouchers(): bool
    {
        return auth()->check()
            && ! $this->isReadOnly()
            && OutletsResource::canEdit($this->getOwnerRecord())
            && OutletsResource::getEloquentQuery()->whereKey($this->getOwnerRecord()->getKey())->exists();
    }
}
