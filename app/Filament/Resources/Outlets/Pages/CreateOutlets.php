<?php

namespace App\Filament\Resources\Outlets\Pages;

use App\Filament\Resources\Outlets\OutletsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOutlets extends CreateRecord
{
    protected static string $resource = OutletsResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
