<?php

declare(strict_types=1);

namespace App\Events;

use Override;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PrepareSwapEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $playerId,
        public int $roundNumber,
        public Carbon $uploadBy
    ) {}

    /**
     * @return array<mixed>
     */
    #[Override]
    public function broadcastOn(): array
    {
        return [new PrivateChannel("player.{$this->playerId}")];
    }

    /**
     * @return array<mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'prepare_swap',
            'payload' => [
                'round_number' => $this->roundNumber,
                'upload_by' => $this->uploadBy->toIso8601String(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
