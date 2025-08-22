<?php

declare(strict_types=1);

namespace App\Events;

class ServerMessageEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public string $text
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'text' => $this->text,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'message';
    }
}
