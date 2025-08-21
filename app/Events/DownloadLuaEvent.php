<?php

declare(strict_types=1);

namespace App\Events;

class DownloadLuaEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public string $luaVersion,
        public string $luaUrl
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'lua_version' => $this->luaVersion,
            'lua_url' => $this->luaUrl,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'download_lua';
    }
}
