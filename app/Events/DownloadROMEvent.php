<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DownloadROMEvent implements ShouldBroadcast
{
    public function __construct(
        public string $playerId,
        public string $romName,
        public string $romUrl
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
            'type' => 'download_rom',
            'payload' => [
                'rom_name' => $this->romName,
                'rom_url' => $this->romUrl,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
