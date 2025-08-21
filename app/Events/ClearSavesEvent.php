<?php

declare(strict_types=1);

namespace App\Events;

class ClearSavesEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
    ) {}

    #[\Override]
    public function getType(): string
    {
        return 'clear_saves';
    }
}
