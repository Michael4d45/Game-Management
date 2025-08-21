<?php

declare(strict_types=1);

namespace App\Events;

class StartGameEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public int $startAt,
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'start_time' => $this->startAt,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'start_game';
    }
}
