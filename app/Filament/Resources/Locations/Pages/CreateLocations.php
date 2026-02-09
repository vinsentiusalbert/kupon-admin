<?php

namespace App\Filament\Resources\Locations\Pages;

use App\Filament\Resources\Locations\LocationsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLocations extends CreateRecord
{
    protected static string $resource = LocationsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
