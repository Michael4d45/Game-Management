<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PauseGameEvent;
use App\Events\SessionEndedEvent;
use App\Events\StartGameEvent;
use App\Models\GameSession;

class GameSessionBroadcast
{
    protected GameSession|null $session = null;

    public static function toSession(GameSession $session): self
    {
        $instance = new self;
        $instance->session = $session;

        return $instance;
    }

    public function start(): void
    {
        if (! $this->session) {
            return;
        }
        broadcast(new StartGameEvent(
            sessionName: $this->session->name
        ));
    }

    public function pause(): void
    {
        if (! $this->session) {
            return;
        }
        broadcast(new PauseGameEvent(
            sessionName: $this->session->name
        ));
    }

    public function end(): void
    {
        if (! $this->session) {
            return;
        }
        broadcast(new SessionEndedEvent(
            sessionName: $this->session->name
        ));
    }
}
