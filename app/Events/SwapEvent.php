<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SwapEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $playerId,
        public int $roundNumber,
        public string $swapAt, // ISO8601 UTC
        public string $newGame,
        public string|null $saveUrl
    ) {}

    /**
     * @return array<mixed>
     */
    #[\Override]
    public function broadcastOn(): array
    {
        // Each player gets their own private channel
        return [new PrivateChannel("player.{$this->playerId}")];
    }

    /**
     * @return array<string,mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'swap',
            'payload' => [
                'round_number' => $this->roundNumber,
                'swap_at' => $this->swapAt,
                'new_game' => $this->newGame,
                'save_url' => $this->saveUrl,
            ],
        ];
    }
}
