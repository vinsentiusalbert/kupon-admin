<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Campaigns\CampaignsResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RunningCampaignsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $runningCount = CampaignsResource::getEloquentQuery()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        return [
            Stat::make('Campaigns Berjalan', $runningCount),
        ];
    }
}
