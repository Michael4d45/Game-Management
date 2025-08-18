<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DownloadLuaEvent implements ShouldBroadcast
{
    public function __construct(
        public string $playerName,
        public string $luaVersion,
        public string $luaUrl
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
            'type' => 'download_lua',
            'payload' => [
                'lua_version' => $this->luaVersion,
                'lua_url' => $this->luaUrl,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'command';
    }
}
