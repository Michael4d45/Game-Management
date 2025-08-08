<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WelcomeEvent implements ShouldBroadcast
{
    public function __construct(
        public string $playerId,
        public string $sessionId,
        public string $mode,
        public int $swapInterval
    ) {}

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function broadcastOn(): array
    {
        return [new PrivateChannel("player.{$this->playerId}")];
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'welcome',
            'payload' => [
                'session_id' => $this->sessionId,
                'player_id' => $this->playerId,
                'mode' => $this->mode,
                'swap_interval' => $this->swapInterval,
            ],
        ];
    }
}
