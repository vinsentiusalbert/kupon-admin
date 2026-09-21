<?php

namespace App\Filament\Resources\Outlets\Pages;

use App\Filament\Resources\Outlets\OutletsResource;
use App\Services\OutletVoucherService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOutlets extends CreateRecord
{
    protected static string $resource = OutletsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();

        return app(OutletVoucherService::class)->createOutlet($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
