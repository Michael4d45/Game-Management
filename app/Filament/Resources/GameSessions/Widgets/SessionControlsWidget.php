<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameSessions\Widgets;

use App\Enums\GameSessionMode;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\SessionPlayer;
use App\Services\GamePlayerBroadcast;
use App\Services\GameSessionBroadcast;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class SessionControlsWidget extends Widget
{
    protected string $view = 'filament.resources.game-session-resource.widgets.session-controls';

    public GameSession|null $record = null;

    public function mount(GameSession $record): void
    {
        $this->record = $record;
    }

    public function start(): void
    {
        if (! $this->record) {
            return;
        }

        $this->record->update([
            'start_at' => now()->addSeconds(3),
        ]);

        GameSessionBroadcast::toSession($this->record)->start();

        Notification::make()
            ->title('Game start scheduled')
            ->success()
            ->send();
    }

    public function pause(): void
    {
        if (! $this->record) {
            return;
        }

        $this->record->update([
            'start_at' => null,
        ]);

        GameSessionBroadcast::toSession($this->record)->pause();

        Notification::make()
            ->title('Game paused')
            ->warning()
            ->send();
    }

    public function triggerSwap(): void
    {
        if (! $this->record) {
            return;
        }

        $swapAt = now()->addSeconds(5);
        $round = $this->record->current_round + 1;

        $this->record->update(['current_round' => $round]);

        foreach ($this->record->players as $player) {
            switch ($this->record->mode) {
                case GameSessionMode::SyncList:
                    GamePlayerBroadcast::toPlayer($player)
                        ->swap(
                            roundNumber: $round,
                            swapAt: $swapAt,
                            newGame: $this->pickNextGame(),
                            saveUrl: null
                        );
                    break;
                case GameSessionMode::SaveSwap:
                    $saveUrl = $this->assignSaveForPlayer($player);
                    GamePlayerBroadcast::toPlayer($player)
                        ->swap(
                            roundNumber: $round,
                            swapAt: $swapAt,
                            newGame: $this->pickNextGameForPlayer($player),
                            saveUrl: $saveUrl
                        );
                    break;
            }
        }

        Notification::make()
            ->title('Swap triggered for all players')
            ->success()
            ->send();
    }

    protected function pickNextGame(): Game
    {
        return Game::firstOrFail();
    }

    protected function pickNextGameForPlayer(SessionPlayer $player): Game
    {
        return Game::firstOrFail();
    }

    protected function assignSaveForPlayer(SessionPlayer $player): string|null
    {
        if (! $this->record) {
            return null;
        }

        return url("/saves/{$this->record->id}/{$player->name}.state");
    }
}
