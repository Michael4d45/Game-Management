<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ClearSavesEvent;
use App\Events\PauseGameEvent;
use App\Events\StartGameEvent;
use App\Events\SwapEvent;
use App\Models\Game;
use App\Models\GameSession;
use Carbon\Carbon;

class GameSessionBroadcast
{
    private string $channel;

    public function __construct(public GameSession $session)
    {
        $this->channel = "session.{$session->name}";
    }

    public function start(): void
    {
        $startAt = $this->session->status_at;
        broadcast(new StartGameEvent(
            channel: $this->channel,
            startAt: $startAt->getTimestamp(),
        ));
    }

    public function pause(): void
    {
        broadcast(new PauseGameEvent(
            channel: $this->channel,
        ));
    }

    public function clearSaves(): void
    {
        broadcast(new ClearSavesEvent(
            channel: $this->channel,
        ));
    }

    public function swap(int $roundNumber, Carbon $swapAt, Game $newGame, string|null $saveUrl = null): void
    {
        broadcast(new SwapEvent(
            channel: $this->channel,
            roundNumber: $roundNumber,
            swapAt: $swapAt,
            newGame: $newGame->file,
            saveUrl: $saveUrl
        ));
    }
}
