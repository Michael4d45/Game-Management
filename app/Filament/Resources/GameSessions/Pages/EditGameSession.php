<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Pages;

use Override;
use App\Filament\Resources\GameSessions\Widgets\ReverbStatusWidget;
use App\Filament\Resources\GameSessions\Widgets\SessionControlsWidget;
use App\Filament\Resources\GameSessions\Widgets\SessionStatsWidget;
use App\Filament\Resources\GameSessions\Widgets\SessionPlayersTable;
use App\Filament\Resources\GameSessions\Widgets\SessionSwapsTable;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\GameSessionResource\Widgets;
use Filament\Resources\Pages\EditRecord;

class EditGameSession extends EditRecord
{
    protected static string $resource = GameSessionResource::class;

    #[Override]
    protected function getFooterWidgets(): array
    {
        return [
            ReverbStatusWidget::class,
            SessionControlsWidget::class,
            SessionStatsWidget::class,
            SessionPlayersTable::class,
            SessionSwapsTable::class,
        ];
    }
}
