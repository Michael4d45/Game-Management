<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use Override;
use App\Models\GameSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SessionStatsWidget extends BaseWidget
{
    public GameSession|null $record = null;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    #[Override]
    protected function getStats(): array
    {
        // @phpstan-ignore property.nonObject
        $players = $this->record->players;

        return [
            Stat::make('Total Players', $players->count()),
            Stat::make('Ready', $players->where('is_ready', true)->count()),
            Stat::make('Connected', $players->where('is_connected', true)->count()),
            Stat::make('Avg Ping', round($players->avg('ping') ?: 0) . ' ms'),
            // Stat::make('Run time', $this->record->run_time),
        ];
    }
}
