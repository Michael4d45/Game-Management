<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessionResource\Widgets;

use App\Models\GameSession;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class SessionControlsWidget extends Widget
{
    protected static string $view = 'filament.resources.game-session-resource.widgets.session-controls';

    public GameSession|null $record = null;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    public function start(): void
    {
        // @phpstan-ignore method.nonObject
        $this->record->update([
            'start_at' => now()->addSeconds(3),
        ]);

        Notification::make()
            ->title('Game start scheduled')
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }

    public function pause(): void
    {
        // @phpstan-ignore method.nonObject
        $this->record->update([
            'start_at' => null,
        ]);

        Notification::make()
            ->title('Game paused')
            ->warning()
            ->send();

        $this->dispatch('$refresh');
    }
}
