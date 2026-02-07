<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaigns extends CreateRecord
{
    protected static string $resource = CampaignsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
