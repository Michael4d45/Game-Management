<?php

declare(strict_types=1);

namespace App\Events;

use Carbon\Carbon;

class PrepareSwapEvent extends CommandEvent
{
    public function __construct(
        public string $channel,
        public int $roundNumber,
        public Carbon $uploadBy
    ) {}

    #[\Override]
    public function getPayload(): array
    {
        return [
            'round_number' => $this->roundNumber,
            'upload_by' => $this->uploadBy->toIso8601String(),
        ];
    }

    #[\Override]
    public function getType(): string
    {
        return 'prepare_swap';
    }
}
