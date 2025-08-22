<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ChangeGameStateEvent;
use App\Events\ClearSavesEvent;
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

    public function stateChange(): void
    {
        broadcast(new ChangeGameStateEvent(
            channel: $this->channel,
            state: $this->session->state,
            stateAt: $this->session->state_at->getTimestamp(),
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
