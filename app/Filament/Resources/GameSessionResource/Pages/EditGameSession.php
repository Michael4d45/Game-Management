<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Pages;

use App\Filament\Resources\GameSessionResource;
use App\Filament\Resources\GameSessionResource\Widgets;
use Filament\Resources\Pages\EditRecord;

class EditGameSession extends EditRecord
{
    protected static string $resource = GameSessionResource::class;

    #[\Override]
    protected function getFooterWidgets(): array
    {
        return [
            Widgets\ReverbStatusWidget::class,
            Widgets\SessionControlsWidget::class,
            Widgets\SessionStatsWidget::class,
            Widgets\SessionPlayersTable::class,
        ];
    }
}
