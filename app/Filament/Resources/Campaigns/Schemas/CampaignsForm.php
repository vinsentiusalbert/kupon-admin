<?php

namespace App\Filament\Resources\Campaigns\Schemas;

use App\Models\Campaigns;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('campaign_name')
                    ->required(),
                TextInput::make('campaign_code')
                    ->required()
                    ->suffixAction(
                        Action::make('generate_campaign_code')
                            ->label('Generate')
                            ->icon(Heroicon::ArrowPath)
                            ->action(function (Set $set): void {
                                $set('campaign_code', self::generateCampaignCodeUnique());
                            })
                    ),
                TextInput::make('campaign_title')
                    ->required(),
                FileUpload::make('logo')
                    ->image()
                    ->disk('public')
                    ->directory('img/logo')
                    ->getUploadedFileUsing(function ($component, string $file, string | array | null $storedFileNames): ?array {
                        $storage = $component->getDisk();
                        $shouldFetchFileInformation = $component->shouldFetchFileInformation();

                        if ($shouldFetchFileInformation && ! $storage->exists($file)) {
                            return null;
                        }

                        $url = url('storage/' . ltrim($file, '/'));

                        return [
                            'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                            'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                            'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                            'url' => $url,
                        ];
                    })
                    ->imagePreviewHeight(80)
                    ->saveUploadedFileUsing(function ($file) {
                        return self::storeAsWebp($file, 'img/logo');
                    }),
                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('img/main')
                    ->getUploadedFileUsing(function ($component, string $file, string | array | null $storedFileNames): ?array {
                        $storage = $component->getDisk();
                        $shouldFetchFileInformation = $component->shouldFetchFileInformation();

                        if ($shouldFetchFileInformation && ! $storage->exists($file)) {
                            return null;
                        }

                        $url = url('storage/' . ltrim($file, '/'));

                        return [
                            'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                            'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                            'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                            'url' => $url,
                        ];
                    })
                    ->imagePreviewHeight(160)
                    ->saveUploadedFileUsing(function ($file) {
                        return self::storeAsWebp($file, 'img/main');
                    }),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
            ]);
    }

    private static function generateCampaignCode(): string
    {
        return strtoupper(Str::random(5));
    }

    private static function generateCampaignCodeUnique(int $maxAttempts = 10): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = self::generateCampaignCode();

            if (! Campaigns::query()->where('campaign_code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper(Str::random(8));
    }

    private static function storeAsWebp($file, string $directory, int $quality = 80): string
    {
        $contents = @file_get_contents($file->getRealPath());
        $image = $contents ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            return $file->store($directory, 'public');
        }

        imagepalettetotruecolor($image);

        ob_start();
        imagewebp($image, null, $quality);
        $webp = ob_get_clean();
        imagedestroy($image);

        $filename = Str::uuid()->toString() . '.webp';
        $path = $directory . '/' . $filename;

        Storage::disk('public')->put($path, $webp);

        return $path;
    }
}
