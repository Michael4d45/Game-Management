<?php

declare(strict_types=1);

namespace App\Events;

use Carbon\Carbon;

class SwapEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public int $roundNumber,
        public Carbon $swapAt,
        public string $newGame,
        public string|null $saveUrl
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'round_number' => $this->roundNumber,
            'swap_at' => $this->swapAt->getTimestamp(),
            'new_game' => $this->newGame,
            'save_url' => $this->saveUrl,
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'swap';
    }
}
