<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Widgets;

use App\Models\GameSession;
use Filament\Widgets\Widget;

class ReverbStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.reverb-status-widget';

    public ?GameSession $record = null;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    protected function getViewData(): array
    {
        return [
            'channel' => "session.{$this->record?->id}",
            'record'  => $this->record,
        ];
    }
}