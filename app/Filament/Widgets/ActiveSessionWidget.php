<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\GameSession;
use App\Models\SessionPlayer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveSessionsWidget extends BaseWidget
{
    #[\Override]
    protected function getStats(): array
    {
        return [
            Stat::make('Active Sessions', GameSession::where('is_active', true)->count())
                ->description('Currently running')
                ->color('success'),
            Stat::make('Total Players', SessionPlayer::where('is_connected', true)->count())
                ->description('Connected players')
                ->color('primary'),
            Stat::make('Ready Players', SessionPlayer::where('is_ready', true)->count())
                ->description('Players ready to start')
                ->color('warning'),
        ];
    }
}
