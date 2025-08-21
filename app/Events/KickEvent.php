<?php

declare(strict_types=1);

namespace App\Events;

class KickEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public string $reason
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'reason' => $this->reason,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'kick';
    }
}
