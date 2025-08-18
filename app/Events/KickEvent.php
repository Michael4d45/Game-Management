<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class KickEvent implements ShouldBroadcast
{
    public function __construct(
        public string $playerName,
        public string $reason
    ) {}

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function broadcastOn(): array
    {
        return [new PrivateChannel("player.{$this->playerName}")];
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'kick',
            'payload' => [
                'reason' => $this->reason,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
