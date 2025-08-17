<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use Override;
use App\Models\GameSession;
use Filament\Widgets\Widget;

class ReverbStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.reverb-status-widget';

    public GameSession|null $record = null;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    #[Override]
    protected function getViewData(): array
    {
        return [
            'channel' => "session.{$this->record?->id}",
            'record' => $this->record,
        ];
    }
}
