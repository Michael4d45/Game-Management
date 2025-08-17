<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ServerMessageEvent implements ShouldBroadcast
{
    public function __construct(
        public string $playerId,
        public string $text
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
            'type' => 'message',
            'payload' => [
                'text' => $this->text,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
