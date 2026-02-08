<?php

namespace App\Filament\Widgets;

use App\Models\Campaigns;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RunningCampaignsStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $runningCount = Campaigns::query()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        return [
            Stat::make('Campaigns Berjalan', $runningCount),
        ];
    }
}
