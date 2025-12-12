<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Services\OnlineStatusService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStatsCard extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $users = User::count();
        return [
            Stat::make(__('Users Registered'), $users),
            Stat::make(__('Users Online'), OnlineStatusService::onlineUsers()->count()),
        ];
    }
}
