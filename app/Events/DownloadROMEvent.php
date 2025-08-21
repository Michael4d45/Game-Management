<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DownloadROMEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public string $romName,
        public string $romUrl
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
                'rom_name' => $this->romName,
                'rom_url' => $this->romUrl,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'download_rom';
    }
}
