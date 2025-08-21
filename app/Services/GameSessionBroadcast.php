<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\ClearSavesEvent;
use App\Events\PauseGameEvent;
use App\Events\StartGameEvent;
use App\Models\GameSession;

class GameSessionBroadcast
{
    private string $channel;

    public function __construct(public GameSession $session)
    {
        $this->channel = "session.{$session->name}";
    }

    public function start(): void
    {
        $startAt = $this->session->start_at;
        if (! $startAt) {
            throw new \Exception('Expected start at for session: ' . $this->session->name);
        }
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
}
